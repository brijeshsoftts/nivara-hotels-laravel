export interface User {
    id: number;
    name: string;
    email: string;
    phone?: string;
    role?: string;
    category?: string | null;
    is_active?: boolean;
    avatar?: {
        id?: string;
        url?: string;
        altText?: string;
    } | null;
    [key: string]: any;
}

export interface SharedProps {
    auth: {
        user: User | null;
    };
    flash?: {
        message?: string | null;
        success?: string | null;
        error?: string | null;
    };
    [key: string]: any;
}

export * from "./shared.types";
export * from "./api.types";
export * from "./enum.types";
export * from "./room.types";
