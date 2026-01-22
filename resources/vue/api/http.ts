import axios, { AxiosInstance, InternalAxiosRequestConfig } from "axios";

const http: AxiosInstance = axios.create({
    headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
    },
});

// Interceptor para adicionar Bearer Token
http.interceptors.request.use(
    (config: InternalAxiosRequestConfig) => {
        const token = localStorage.getItem("token");

        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }

        return config;
    },
    (error) => {
        return Promise.reject(error);
    }
);

export default http;
