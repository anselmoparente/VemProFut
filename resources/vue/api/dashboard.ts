import http from "./http";
import type { GameListItem } from "./games.ts";

export type DashboardResponse = {
    sports_centers_count: number;
    fields_count: number;
    upcoming_games_count: number;
    upcoming_games: GameListItem[];
};

export const getDashboard = () =>
    http.get<DashboardResponse>("/api/dashboard").then((r) => r.data);
