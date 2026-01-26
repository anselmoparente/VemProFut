import http from "./http";

export type GameStatus = {
  id: number;
  code: string;
  description: string;
};

export type GameListItem = {
  id: number;
  game_date: string;
  start_time: string;
  end_time: string;
  max_players: number;
  status_id: number;
  field_id: number;
  organizer_id: number;
  status?: GameStatus;
  field?: { id: number; name: string; sports_center_id: number; sportsCenter?: { id: number; name: string; city: string; state: string } };
};

export type Paginated<T> = {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
};

export const listGames = (params: {
  page?: number;
  sports_center_id?: number;
  date_from?: string;
  date_to?: string;
  status_id?: number;
}) => {
  const sp = new URLSearchParams();
  if (params.page) sp.set("page", String(params.page));
  if (params.sports_center_id) sp.set("sports_center_id", String(params.sports_center_id));
  if (params.date_from) sp.set("date_from", params.date_from);
  if (params.date_to) sp.set("date_to", params.date_to);
  if (params.status_id) sp.set("status_id", String(params.status_id));

  return http.get<Paginated<GameListItem>>(`/api/games?${sp.toString()}`).then((r) => r.data);
};

export const listGamesBySportsCenter = (sportsCenterId: number, params?: {
  page?: number;
  date_from?: string;
  date_to?: string;
  status_id?: number;
}) => {
  const sp = new URLSearchParams();
  if (params?.page) sp.set("page", String(params.page));
  if (params?.date_from) sp.set("date_from", params.date_from);
  if (params?.date_to) sp.set("date_to", params.date_to);
  if (params?.status_id) sp.set("status_id", String(params.status_id));

  const qs = sp.toString();
  return http.get<Paginated<GameListItem>>(`/api/sports-centers/${sportsCenterId}/games${qs ? `?${qs}` : ""}`).then((r) => r.data);
};

export const listGameStatuses = () =>
  http.get<GameStatus[]>("/api/game-statuses").then((r) => r.data);

export const updateGameStatus = (gameId: number, status_id: number) =>
  http.patch<GameListItem>(`/api/games/${gameId}/status`, { status_id }).then((r) => r.data);
