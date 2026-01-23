import http from "./http";

export type Field = {
    id: number;
    sports_center_id: number;
    name: string;
    price_per_hour: string;
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

export type FieldCreatePayload = {
    name: string;
    price_per_hour: number;
};

export type FieldUpdatePayload = Partial<FieldCreatePayload>;

export const listFields = (sportsCenterId: number, page = 1) =>
    http
        .get<Paginated<Field>>(`/api/sports-centers/${sportsCenterId}/fields?page=${page}`)
        .then((r) => r.data);

export const createField = (sportsCenterId: number, payload: FieldCreatePayload) =>
    http.post<Field>(`/api/sports-centers/${sportsCenterId}/fields`, payload).then((r) => r.data);

export const updateField = (id: number, payload: FieldUpdatePayload) =>
    http.put<Field>(`/api/fields/${id}`, payload).then((r) => r.data);

export const deleteField = (id: number) =>
    http.delete(`/api/fields/${id}`).then((r) => r.data);
