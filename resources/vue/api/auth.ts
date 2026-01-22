import http from "./http";

export type ClientType = "mobile" | "web";

interface RegisterPayload {
    name: string;
    email: string;
    password: string;
    client_type: ClientType;
}

interface LoginPayload {
    email: string;
    password: string;
}

export interface AuthResponse<TUser = any> {
    token: string;
    user: TUser;
}

export const register = (payload: RegisterPayload) =>
    http.post<AuthResponse>("/api/register", payload).then((r) => r.data);

export const login = (payload: LoginPayload) =>
    http.post<AuthResponse>("/api/login", payload).then((r) => r.data);

export const logout = () => http.post("/api/logout").then((r) => r.data);