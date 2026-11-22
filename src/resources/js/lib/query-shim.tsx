import React, { createContext, useContext, useEffect, useState, useMemo } from "react";
import { usePage, router } from "@inertiajs/react";

function resolvePropsByQueryKey(props: any, queryKey: any[]) {
  if (!props || !queryKey || !Array.isArray(queryKey)) return null;

  const keyString = queryKey
    .map((k) => (typeof k === "string" ? k.toLowerCase() : typeof k === "object" ? JSON.stringify(k) : ""))
    .join(" ");

  // Dashboard keys
  if (keyString.includes("revenue") || keyString.includes("revenuetrend")) {
    return props.revenueTrend ?? [];
  }
  if (keyString.includes("bookingstatus") || keyString.includes("booking-status")) {
    return props.bookingStatus ?? [];
  }
  if (keyString.includes("roomstatus") || keyString.includes("room-status")) {
    return props.roomStatus ?? {};
  }
  if (keyString.includes("stays")) {
    return props.stays ?? { arrivals: { total: 0, items: [] }, departures: { total: 0, items: [] } };
  }
  if (keyString.includes("nextbooking") || keyString.includes("next-booking")) {
    return props.nextBooking ?? null;
  }

  // Stats
  if (keyString.includes("stats") || keyString.includes("stat")) {
    return props.stats ?? [];
  }

  // Users
  if (keyString.includes("user")) {
    if (keyString.includes("detail")) {
      return props.user ?? props.users?.data?.[0] ?? null;
    }
    return props.users ?? { data: [], meta: { page: 1, limit: 10, total: 0, totalPages: 1 } };
  }

  // Staff
  if (keyString.includes("staff")) {
    if (keyString.includes("detail")) {
      return props.staffMember ?? props.staff?.data?.[0] ?? null;
    }
    return props.staff ?? { data: [], meta: { page: 1, limit: 10, total: 0, totalPages: 1 } };
  }

  // Customers
  if (keyString.includes("customer")) {
    if (keyString.includes("search")) {
      return props.customers?.data ?? [];
    }
    if (keyString.includes("booking")) {
      return props.customerBookings ?? [];
    }
    if (keyString.includes("payment")) {
      return props.customerPayments ?? [];
    }
    if (keyString.includes("detail")) {
      return props.customer ?? props.customers?.data?.[0] ?? null;
    }
    return props.customers ?? { data: [], meta: { page: 1, limit: 10, total: 0, totalPages: 1 } };
  }

  // Room Types
  if (keyString.includes("roomtype") || keyString.includes("room-type")) {
    return props.roomTypes ?? { data: [] };
  }

  // Amenities
  if (keyString.includes("amenit")) {
    return props.amenities ?? { data: [] };
  }

  // Physical Rooms & Public Available Rooms
  if (keyString.includes("availableroom") || keyString.includes("available-room")) {
    return props.rooms ?? props.featuredRooms ?? { data: [] };
  }
  if (keyString.includes("room")) {
    if (keyString.includes("detail")) {
      return props.room ?? props.rooms?.data?.[0] ?? null;
    }
    return props.rooms ?? { data: [], meta: { page: 1, limit: 10, total: 0, totalPages: 1 } };
  }

  // Bookings
  if (keyString.includes("booking")) {
    if (keyString.includes("detail")) {
      return props.booking ?? props.bookings?.data?.[0] ?? null;
    }
    return props.bookings ?? { data: [], meta: { page: 1, limit: 10, total: 0, totalPages: 1 } };
  }

  // Tasks
  if (keyString.includes("task")) {
    if (keyString.includes("lookup")) {
      if (keyString.includes("housekeeper") || keyString.includes("staff")) {
        return props.staff?.data ?? props.staff ?? [];
      }
      return props.rooms?.data ?? props.rooms ?? [];
    }
    return props.tasks ?? { data: [], meta: { page: 1, limit: 10, total: 0, totalPages: 1 } };
  }

  // Issues
  if (keyString.includes("issue")) {
    return props.issues ?? { data: [], meta: { page: 1, limit: 10, total: 0, totalPages: 1 } };
  }

  // Invoices
  if (keyString.includes("invoice")) {
    return props.invoices ?? { data: [], meta: { page: 1, limit: 10, total: 0, totalPages: 1 } };
  }

  // Payments
  if (keyString.includes("payment")) {
    return props.payments ?? { data: [], meta: { page: 1, limit: 10, total: 0, totalPages: 1 } };
  }

  // Inquiries
  if (keyString.includes("inquir")) {
    return props.inquiries ?? { data: [], meta: { page: 1, limit: 10, total: 0, totalPages: 1 } };
  }

  // Audit Logs
  if (keyString.includes("audit")) {
    return props.auditLogs ?? { data: [], meta: { page: 1, limit: 10, total: 0, totalPages: 1 } };
  }

  // Profile
  if (keyString.includes("profile") || keyString.includes("me")) {
    return props.profile ?? props.auth?.user ?? null;
  }

  // Notifications
  if (keyString.includes("notification")) {
    return props.notifications ?? [];
  }

  return null;
}

export function useQuery<TData = any, TError = any>(options: any): any {
  let props: any = {};
  try {
    const page = usePage();
    props = page.props || {};
  } catch {
    // Outside Inertia
  }

  const resolved = useMemo(() => {
    let raw = resolvePropsByQueryKey(props, options.queryKey);
    if (raw && typeof options.select === "function") {
      try {
        return options.select(raw);
      } catch {
        return raw;
      }
    }
    return raw;
  }, [props, options.queryKey, options.select]);

  const [data, setData] = useState<any>(resolved);

  useEffect(() => {
    setData(resolved);
  }, [resolved]);

  const refetch = () => {
    try {
      router.reload({ preserveScroll: true, preserveState: true });
    } catch {
      // Ignore
    }
    return Promise.resolve({ data: resolved });
  };

  return {
    data,
    isLoading: false,
    isFetching: false,
    isPending: false,
    isSuccess: true,
    isError: false,
    error: null,
    status: "success",
    refetch,
  };
}

export function useMutation<TData = any, TError = any, TVariables = any>(options: any = {}): any {
  const [isPending, setIsPending] = useState(false);
  const [isSuccess, setIsSuccess] = useState(false);
  const [isError, setIsError] = useState(false);
  const [error, setError] = useState<any>(null);

  const mutate = (variables?: any) => {
    setIsPending(true);
    setIsError(false);
    setIsSuccess(false);

    if (typeof options.mutationFn === "function") {
      try {
        const result = options.mutationFn(variables);
        if (result && typeof result.then === "function") {
          result
            .then((res: any) => {
              setIsPending(false);
              setIsSuccess(true);
              options.onSuccess?.(res, variables);
              options.onSettled?.(res, null, variables);
            })
            .catch((err: any) => {
              setIsPending(false);
              setIsError(true);
              setError(err);
              options.onError?.(err, variables);
              options.onSettled?.(null, err, variables);
            });
          return;
        }
        setIsPending(false);
        setIsSuccess(true);
        options.onSuccess?.(result, variables);
        options.onSettled?.(result, null, variables);
      } catch (err: any) {
        setIsPending(false);
        setIsError(true);
        setError(err);
        options.onError?.(err, variables);
        options.onSettled?.(null, err, variables);
      }
    } else {
      setIsPending(false);
      setIsSuccess(true);
      options.onSuccess?.(null, variables);
      options.onSettled?.(null, null, variables);
    }
  };

  const mutateAsync = (variables?: any) => {
    return new Promise((resolve, reject) => {
      setIsPending(true);
      if (typeof options.mutationFn === "function") {
        try {
          const result = options.mutationFn(variables);
          if (result && typeof result.then === "function") {
            result
              .then((res: any) => {
                setIsPending(false);
                setIsSuccess(true);
                options.onSuccess?.(res, variables);
                options.onSettled?.(res, null, variables);
                resolve(res);
              })
              .catch((err: any) => {
                setIsPending(false);
                setIsError(true);
                setError(err);
                options.onError?.(err, variables);
                options.onSettled?.(null, err, variables);
                reject(err);
              });
            return;
          }
          setIsPending(false);
          setIsSuccess(true);
          options.onSuccess?.(result, variables);
          options.onSettled?.(result, null, variables);
          resolve(result);
        } catch (err) {
          setIsPending(false);
          setIsError(true);
          setError(err);
          options.onError?.(err, variables);
          options.onSettled?.(null, err, variables);
          reject(err);
        }
      } else {
        setIsPending(false);
        resolve(null);
      }
    });
  };

  return {
    mutate,
    mutateAsync,
    isPending,
    isLoading: isPending,
    isSuccess,
    isError,
    error,
    reset: () => {
      setIsPending(false);
      setIsSuccess(false);
      setIsError(false);
      setError(null);
    },
  };
}

const queryClientInstance = {
  invalidateQueries: () => {
    try {
      router.reload({ preserveScroll: true, preserveState: true });
    } catch {}
  },
  refetchQueries: () => {
    try {
      router.reload({ preserveScroll: true, preserveState: true });
    } catch {}
  },
  setQueryData: () => {},
  getQueryData: () => null,
  cancelQueries: () => {},
  resetQueries: () => {},
};

export function useQueryClient() {
  return queryClientInstance;
}

export class QueryClient {
  invalidateQueries() {
    try {
      router.reload({ preserveScroll: true, preserveState: true });
    } catch {}
  }
  refetchQueries() {
    try {
      router.reload({ preserveScroll: true, preserveState: true });
    } catch {}
  }
}

export function QueryClientProvider({ children }: { children: any }) {
  return <>{children}</>;
}
