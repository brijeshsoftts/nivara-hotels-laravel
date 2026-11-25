import { toast } from "sonner";

function notFoundMessage(method: unknown): string {
    switch (String(method ?? "").toUpperCase()) {
        case "DELETE":
            return "Could not delete this item because it is no longer available. Refresh and try again.";
        case "PATCH":
        case "PUT":
            return "Could not update this item because it is no longer available. Refresh and try again.";
        case "POST":
            return "Could not complete this action because a required item is no longer available. Refresh and try again.";
        default:
            return "The requested information is no longer available. Refresh and try again.";
    }
}

export function notifyError(error: unknown) {
    if (typeof error === "string") {
        toast.error(error);
        return;
    }

    if (error && typeof error === "object") {
        const errObj = error as Record<string, any>;
        const response = errObj.response as Record<string, any> | undefined;

        if (response?.status === 404) {
            toast.error(
                notFoundMessage(
                    errObj.config?.method ?? response.config?.method,
                ),
            );
            return;
        }

        const errors = errObj.errors || errObj.error?.errors || errObj;
        if (errors && typeof errors === "object") {
            const first = Object.values(errors)[0];
            if (typeof first === "string") {
                toast.error(first);
                return;
            }
            if (
                first &&
                typeof first === "object" &&
                "message" in (first as any)
            ) {
                toast.error((first as any).message);
                return;
            }
        }

        const message = response?.data?.message || errObj.message;
        if (typeof message === "string") {
            toast.error(message);
            return;
        }
    }

    toast.error("Something went wrong.");
}
