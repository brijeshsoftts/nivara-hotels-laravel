import { toast } from "sonner";

export function notifyError(error: unknown) {
  if (typeof error === "string") {
    toast.error(error);
    return;
  }

  if (error && typeof error === "object") {
    const errObj = error as Record<string, any>;
    if (errObj.message) {
      toast.error(errObj.message);
      return;
    }
    const errors = errObj.errors || errObj.error?.errors;
    if (errors && typeof errors === "object") {
      const first = Object.values(errors)[0];
      if (typeof first === "string") {
        toast.error(first);
        return;
      }
      if (first && typeof first === "object" && "message" in (first as any)) {
        toast.error((first as any).message);
        return;
      }
    }
  }

  toast.error("Something went wrong.");
}
