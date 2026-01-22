import http from "./http";

export type SportsCenter = {
    id: number;
    owner_id: number;
    name: string;
    phone: string | null;
    street: string;
    number: string;
    complement?: string | null;
    neighborhood: string;
    city: string;
    state: string;
    zip_code: string;
    latitude: number;
    longitude: number;
    created_at?: string;
    updated_at?: string;
};

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
};

export type SportsCenterCreatePayload = {
    name: string;
    phone: string | null;
    street: string;
    number: string;
    complement?: string | null;
    neighborhood: string;
    city: string;
    state: string;
    zip_code: string;
    latitude: number;
    longitude: number;
};

export type SportsCenterUpdatePayload = Partial<SportsCenterCreatePayload>;

export const listSportsCenters = (page = 1) =>
    http.get<Paginated<SportsCenter>>(`/api/sports-centers?page=${page}`).then((r) => r.data);

export const showSportsCenter = (id: number) =>
    http.get<SportsCenter>(`/api/sports-centers/${id}`).then((r) => r.data);

export const createSportsCenter = (payload: SportsCenterCreatePayload) =>
    http.post<SportsCenter>("/api/sports-centers", payload).then((r) => r.data);

export const updateSportsCenter = (id: number, payload: SportsCenterUpdatePayload) =>
    http.put<SportsCenter>(`/api/sports-centers/${id}`, payload).then((r) => r.data);

export const deleteSportsCenter = (id: number) =>
    http.delete(`/api/sports-centers/${id}`).then((r) => r.data);