import { useEffect, useMemo, useRef, useState } from "react";
import { router } from "@inertiajs/react";

import type { ListParams } from "@/types/api.types";
import { useDebounce } from "./useDebounce";

const RESERVED_KEYS = new Set(["limit", "page", "search", "filters"]);

export function useListParams(initial: Partial<ListParams> = {}) {
  const getSearchString = () => (typeof window !== "undefined" ? window.location.search : "");
  const [paramsString, setParamsString] = useState(getSearchString);

  const [filters, setFilters] = useState<Partial<ListParams>>(() => {
    const result: Record<string, unknown> = { ...initial };
    RESERVED_KEYS.forEach((key) => delete result[key]);

    new URLSearchParams(getSearchString()).forEach((value, key) => {
      if (!RESERVED_KEYS.has(key)) {
        result[key] = value;
      }
    });

    return result as Partial<ListParams>;
  });

  const searchParams = new URLSearchParams(paramsString);

  const [page, setPage] = useState(
    () => Number(searchParams.get("page")) || initial.page || 1
  );

  const [searchInput, setSearchInput] = useState(
    () => searchParams.get("search") ?? initial.search ?? ""
  );

  const search = useDebounce(searchInput);
  const lastWrittenUrl = useRef<string | null>(null);

  useEffect(() => {
    const onPopState = () => {
      setParamsString(getSearchString());
    };
    window.addEventListener("popstate", onPopState);
    return () => window.removeEventListener("popstate", onPopState);
  }, []);

  useEffect(() => {
    if (lastWrittenUrl.current === paramsString) {
      lastWrittenUrl.current = null;
      return;
    }

    const url = new URLSearchParams(paramsString);
    const nextFilters: Record<string, unknown> = {};

    url.forEach((value, key) => {
      if (!RESERVED_KEYS.has(key)) {
        nextFilters[key] = value;
      }
    });

    setFilters(nextFilters as Partial<ListParams>);
    setPage(Number(url.get("page")) || 1);
    setSearchInput(url.get("search") ?? "");
  }, [paramsString]);

  const params = useMemo(
    () =>
      ({
        ...filters,
        limit: Number(searchParams.get("limit")) || 10,
        page,
        search: search || undefined,
      }) as ListParams,
    [filters, page, search, searchParams]
  );

  useEffect(() => {
    const timeout = setTimeout(() => {
      const next = new URLSearchParams(paramsString);

      next.set("limit", String(params.limit));
      next.set("page", String(params.page));

      if (params.search) {
        next.set("search", params.search);
      } else {
        next.delete("search");
      }

      for (const key of Array.from(next.keys())) {
        if (!RESERVED_KEYS.has(key) && !(key in filters)) {
          next.delete(key);
        }
      }

      Object.entries(filters as Record<string, unknown>).forEach(
        ([key, value]) => {
          if (RESERVED_KEYS.has(key)) return;

          if (value == null || value === "") {
            next.delete(key);
          } else if (typeof value !== "object") {
            next.set(key, String(value));
          }
        }
      );

      next.delete("filters");

      const nextStr = next.toString();
      if (nextStr !== paramsString.replace(/^\?/, "")) {
        lastWrittenUrl.current = nextStr;
        setParamsString(`?${nextStr}`);
        if (typeof window !== "undefined") {
          const targetUrl = `${window.location.pathname}${nextStr ? `?${nextStr}` : ""}`;
          router.get(targetUrl, {}, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
          });
        }
      }
    }, 400);

    return () => clearTimeout(timeout);
  }, [filters, params, paramsString]);

  return {
    params,
    filters,
    searchInput,

    setSearch: (value: string) => {
      setSearchInput(value);
      setPage(1);
    },

    setPage,

    setFilter: (patch: Partial<ListParams>) => {
      const {
        page: _page,
        limit: _limit,
        search: _search,
        ...filterPatch
      } = patch;

      setFilters((prev) => {
        const next: Record<string, unknown> = { ...prev };

        Object.entries(filterPatch as Record<string, unknown>).forEach(
          ([key, value]) => {
            const isEmpty =
              value === "" ||
              value === null ||
              value === undefined ||
              (typeof value === "object" &&
                !Array.isArray(value) &&
                Object.keys(value as Record<string, unknown>).length === 0) ||
              (Array.isArray(value) && value.length === 0);

            if (isEmpty) {
              delete next[key];
            } else {
              next[key] = value;
            }
          }
        );

        return next as Partial<ListParams>;
      });
      setPage(1);
    },
  };
}
