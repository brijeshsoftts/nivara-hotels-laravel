import { useEffect, useState } from "react";
import { usePage } from "@inertiajs/react";
import type { RoomTypeDetails } from "../roomTypes.types";

export function useRoomType(slug: string) {
  const page = usePage<any>();
  const roomTypes = page.props?.roomTypes?.data ?? [];
  const propFound = slug
    ? (roomTypes.find(
        (rt: any) => rt.slug === slug || String(rt.id) === String(slug)
      ) ?? null)
    : null;

  const [fetched, setFetched] = useState<RoomTypeDetails | null>(null);
  const [isLoading, setIsLoading] = useState(false);
  const [isError, setIsError] = useState(false);
  const [attempt, setAttempt] = useState(0);

  useEffect(() => {
    if (!slug || propFound) {
      setFetched(null);
      setIsLoading(false);
      setIsError(false);
      return;
    }

    const controller = new AbortController();
    setIsLoading(true);
    setIsError(false);

    void (async () => {
      try {
        const response = await fetch(
          `/room-types/${encodeURIComponent(slug)}`,
          {
            headers: { Accept: "application/json" },
            signal: controller.signal,
          }
        );

        if (!response.ok) {
          throw new Error("Unable to load room type details.");
        }

        const result = (await response.json()) as { data: RoomTypeDetails };
        setFetched(result.data);
      } catch {
        if (!controller.signal.aborted) {
          setFetched(null);
          setIsError(true);
        }
      } finally {
        if (!controller.signal.aborted) {
          setIsLoading(false);
        }
      }
    })();

    return () => controller.abort();
  }, [slug, propFound, attempt]);

  return {
    roomType: (propFound ?? fetched) as RoomTypeDetails | null,
    isLoading: !propFound && isLoading,
    isError: !propFound && isError,
    refetch: () => setAttempt((current) => current + 1),
  };
}
