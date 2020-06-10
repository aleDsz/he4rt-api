defmodule He4rt.Routes.CouponsRouter do
  @moduledoc false
  use Plug.Router
  import He4rt.Controllers.CouponsController
  import He4rt.Views.ErrorView

  plug :match
  plug He4rt.Plugs.Params
  plug He4rt.Plugs.Authentications
  plug :dispatch

  post "/",
    do: create(conn, conn.assigns.params)

  match _ do
    conn
    |> route_not_found()
  end
end
