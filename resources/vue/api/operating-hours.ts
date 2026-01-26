import http from "./http";

export type OperatingHour = {
    id: number;
    sports_center_id: number;
    day_of_week: number;
    open_time: string;
    close_time: string;
    created_at?: string;
    updated_at?: string;
};

export type OperatingHourItemPayload = {
    day_of_week: number;
    open_time: string;
    close_time: string;
};

export type UpsertOperatingHoursPayload = {
    items: OperatingHourItemPayload[];
};

export const listOperatingHours = (sportsCenterId: number) =>
    http.get<OperatingHour[]>(`/api/sports-centers/${sportsCenterId}/operating-hours`).then((r) => r.data);

export const upsertOperatingHours = (sportsCenterId: number, payload: UpsertOperatingHoursPayload) =>
    http.put<OperatingHour[]>(`/api/sports-centers/${sportsCenterId}/operating-hours`, payload).then((r) => r.data);
