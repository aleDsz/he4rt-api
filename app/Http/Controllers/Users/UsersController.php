<?php
/**
 * Created by PhpStorm.
 * User: Daniel Reis
 * Date: 2/23/2019
 * Time: 12:05 AM
 */

namespace App\Http\Controllers\Users;


use App\Entities\Auth\User;
use App\Entities\Coupons\Coupon;
use App\Entities\Levelup\Level;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    use ApiResponse;

    /**
     * @OA\Get(
     *     path="/users",
     *     summary="Lista todos os usuários",
     *     operationId="GetUsers",
     *     tags={"users"},
     *     @OA\Parameter(
     *         name="date",
     *         in="query",
     *         description="Data para filtrar",
     *         required=false,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="...",
     *     )
     * )
     */

    public function index(Request $request)
    {
        $user = new User();
        if ($request->has('date')) {
            $user = User::whereDate('created_at', '=', $request->input('date'))->get();
        } else {
            $user = User::paginate(15);
        }
        return $this->success($user);
    }

    /**
     * @OA\Get(
     *     path="/users/{discord_id}",
     *     summary="Mostra as informações de um usuário",
     *     operationId="GetUser",
     *     tags={"users"},
     *     @OA\Parameter(
     *         name="discord_id",
     *         in="path",
     *         description="ID do usuário do Discord",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="...",
     *     )
     * )
     */

    public function show(Request $request, $discord_id)
    {
        $request->merge(['discord_id' => $discord_id]);
        $this->validate($request, [
            'discord_id' => 'required|exists:users'
        ]);

        return $this->success(User::where('discord_id', $request->input('discord_id'))->first());
    }

    /**
     * @OA\Post(
     *     path="/users",
     *     summary="Cria um novo usuário",
     *     operationId="StoreUser",
     *     tags={"users"},
     *     @OA\Parameter(
     *         name="discord_id",
     *         in="query",
     *         description="ID do usuário do Discord",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="...",
     *     )
     * )
     */

    public function store(Request $request)
    {
        $this->validate($request, [
            'discord_id' => 'required|unique:users'
        ]);

        return $this->success(User::create(['discord_id' => $request->input('discord_id')]));
    }


    /**
     * @OA\Put(
     *     path="/users/{discord_id}",
     *     summary="Altera um usuário",
     *     operationId="StoreUser",
     *     tags={"users"},
     *     @OA\Parameter(
     *         name="discord_id",
     *         in="path",
     *         description="ID do usuário do Discord",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="name",
     *         in="query",
     *         description="Nome da pessoa",
     *         required=false,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="nickname",
     *         in="query",
     *         description="Apelido da pessoa",
     *         required=false,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="git",
     *         in="query",
     *         description="Link do git",
     *         required=false,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="language",
     *         in="query",
     *         description="Linguagem em formato 'Locale'",
     *         required=false,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="about",
     *         in="query",
     *         description="Informações pessoais do usuário",
     *         required=false,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="...",
     *     )
     * )
     */

    public function update(Request $request, $discord_id)
    {
        $request->merge(['discord_id' => $discord_id]);
        $this->validate($request, [
            'discord_id' => 'required|exists:users',
            'name' => 'string',
            'nickname' => 'string',
            'language' => 'string',
            'git' => 'string',
            'about' => 'string'
        ]);

        $fields = $request->except(['level', 'current_exp', 'money', 'discord_id']);
        $user = User::where('discord_id', $discord_id)->first();
        $user->update($fields);

        return $this->success($user);
    }

    public function wipe(Request $request)
    {

        $this->validate($request, [
            'discord_ids' => 'required|array'
        ]);

        User::truncate();
        foreach ($request->input('discord_ids') as $id) {
            User::create(['discord_id' => $id]);
        }
        return $this->success(['users' => User::count()]);
    }

    /**
     * @OA\Post(
     *     path="/users/{discord_id}/daily",
     *     summary="Gerador de hCoins diário",
     *     operationId="Coins",
     *     tags={"users"},
     *     @OA\Parameter(
     *         name="discord_id",
     *         in="path",
     *         description="ID do usuário do Discord",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="...",
     *     )
     * )
     */
    public function daily(Request $request, $discord_id)
    {
        $request->merge(['discord_id' => $discord_id]);
        $this->validate($request, [
            'discord_id' => 'required|exists:users'
        ]);

        $user = User::where('discord_id', $request->input('discord_id'))->first();

        if ($user->money != 0) {
            $check = Carbon::now();
            $days = $check->diffInDays($user->daily);

            if ($days == 0) {
                $diff = $check->diff($user->daily);

                $hour = $diff->format("%h");
                $minutes = $diff->format("%i");
                $seconds = $diff->format("%s");

                $hour = 23 - $hour;
                $minutes = 59 - $minutes;
                $seconds = 59 - $seconds;

                $time = $hour . 'h' . $minutes . 'm' . $seconds . 's';

                return $this->unprocessable(['error_code' => 'already used today',
                    'time' => $time]);
            }
        }

        $daily = rand(250, 500);

        $user->money = $daily;
        $user->daily = Carbon::now();
        $user->save();

        return $this->success(['discord_id' => $user->discord_id, 'daily' => $daily]);
    }

    public function addMoney(Request $request, $discord_id)
    {
        $request->merge(['discord_id' => $discord_id]);
        $this->validate($request, [
            'discord_id' => 'required|exists:users',
            'value' => 'required|integer'
        ]);

        $user = User::where('discord_id', $discord_id)->first();

        $user->money += $request->input('value');
        $user->save();

        return $this->success($user);
    }

    public function reduceMoney(Request $request, $discord_id)
    {
        $request->merge(['discord_id' => $discord_id]);
        $this->validate($request, [
            'discord_id' => 'required|exists:users',
            'value' => 'required|integer'
        ]);

        $user = User::where('discord_id', $discord_id)->first();

        $user->money -= $request->input('value');
        $user->save();

        return $this->success($user);
    }

    public function coupon(Request $request, $discord_id)
    {
        $request->merge(['discord_id' => $discord_id]);
        $this->validate($request, [
            'discord_id' => 'required|exists:users',
            'coupon' => 'required|exists:coupons,name'
        ]);

        $user = User::where('discord_id', $discord_id)->first();
        $coupon = Coupon::where([
            [
                'name', '=' ,$request->input('coupon')
            ],
            [
                'used', '=', false
            ]
        ])->orderBy('id', 'DESC')->first();

        if (!$coupon) {
            return $this->unprocessable(['error' => 'This coupon already was used or not found']);
        }

        if ($coupon->type_id == 1) {
            $user->current_exp += $coupon->value;

        } else {
            $user->money += $coupon->value;
        }

        $user->save();

        $coupon->used = true;
        $coupon->user_id = $user->id;
        $coupon->save();

        return $this->success(['user' => $user, 'coupon' => $coupon]);
    }


    /**
     * @OA\Post(
     *     path="/users/{discord_id}/reputation",
     *     summary="Adiciona reputation para um usuário",
     *     operationId="Reputation",
     *     tags={"users"},
     *     @OA\Parameter(
     *         name="discord_id",
     *         in="path",
     *         description="ID do Discord do usuário que está enviando a reputation",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="receive_id",
     *         in="query",
     *         description="ID do Discord do usuário que está recebendo a reputation",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="...",
     *     )
     * )
     */
    public function reputation(Request $request, $discord_id)
    {
        $request->merge(['discord_id' => $discord_id]);
        $this->validate($request, [
            'discord_id' => 'required|exists:users',
            'receive_id' => 'required|exists:users,discord_id|different:discord_id'
        ]);

        $sender = User::where('discord_id', $discord_id)->first();
        $receiver = User::where('discord_id', $request->input('receive_id'))->first();

        $reputation = $sender->reputation()->orderBy('reputation_logs.created_at', 'DESC')->first();

        if ($reputation) {
            $check = new Carbon(Carbon::now());
            $days = $check->diffInDays($reputation->pivot->created_at);

            if ($days == 0) {
                $diff = $check->diff($reputation->pivot->created_at);
                $hour = $diff->format("%h");
                $minutes = $diff->format("%i");

                $hour = 23 - $hour;
                $minutes = 59 - $minutes;

                $time = $hour . 'h' . $minutes . 'm';
                return $this->unprocessable(['error_code' => 'already.used.today',
                    'real_time' => $time]);
            }
        }

        $sender->reputation()->attach($receiver->id);

        $receiver->reputation++;

        $receiver->save();

        return $this->success();
    }
}