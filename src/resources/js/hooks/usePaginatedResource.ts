import { useEffect, useState } from "react";
import type { ListParams, Pagination } from "@/types/api.types";

interface PaginatedPayload<T> {
  data?: T[];
  meta?: Pagination;
}

const DEFAULT_META: Pagination = {
  page: 1,
  limit: 10,
  total: 0,
};

/**
 * Fetches paginated list data from a JSON endpoint while staying in sync with
 * Inertia page props after mutations.
 */
export function usePaginatedResource<T = any>(
  endpoint: string,
  initialData?: PaginatedPayload<T>,
  params?: ListParams
) {
  const initialMeta = initialData?.meta ?? DEFAULT_META;
  const page = params?.page ?? 1;
  const limit = params?.limit ?? initialMeta.limit ?? 10;
  const search = params?.search ?? "";

  const [items, setItems] = useState<T[]>(() => initialData?.data ?? []);
  const [pagination, setPagination] = useState<Pagination>(
    () => initialData?.meta ?? { page, limit, total: initialData?.data?.length ?? 0 }
  );
  const [isLoading, setIsLoading] = useState(false);
  const [isError, setIsError] = useState(false);
  const [attempt, setAttempt] = useState(0);

  const matchesInitial =
    attempt === 0 &&
    page === (initialMeta.page ?? 1) &&
    limit === (initialMeta.limit ?? 10) &&
    search === "" &&
    Boolean(initialData?.meta);

  useEffect(() => {
    if (matchesInitial) {
      setItems(initialData?.data ?? []);
      setPagination(initialData?.meta ?? DEFAULT_META);
      setIsLoading(false);
      setIsError(false);
      return;
    }

    const controller = new AbortController();
    const query = new URLSearchParams({
      page: String(page),
      limit: String(limit),
    });
    if (search) {
      query.set("search", search);
    }

    setIsLoading(true);
    setIsError(false);

    void (async () => {
      try {
        const response = await fetch(`${endpoint}?${query.toString()}`, {
          headers: { Accept: "application/json" },
          signal: controller.signal,
        });

        if (!response.ok) {
          throw new Error(`Failed to fetch ${endpoint}`);
        }

        const result = (await response.json()) as {
          data?: T[];
          meta?: Pagination;
        };

        if (!controller.signal.aborted) {
          setItems(result.data ?? []);
          setPagination(
            result.meta ?? {
              page,
              limit,
              total: result.data?.length ?? 0,
            }
          );
        }
      } catch {
        if (!controller.signal.aborted) {
          setIsError(true);
        }
      } finally {
        if (!controller.signal.aborted) {
          setIsLoading(false);
        }
      }
    })();

    return () => controller.abort();
  }, [endpoint, page, limit, search, attempt, matchesInitial, initialData]);

  return {
    items,
    pagination,
    isLoading,
    isError,
    refetch: () => setAttempt((current) => current + 1),
  };
}

