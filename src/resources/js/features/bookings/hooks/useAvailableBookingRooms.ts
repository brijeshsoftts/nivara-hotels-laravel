import { usePage } from "@inertiajs/react";
import { useEffect, useState } from "react";
import type {
  AvailableBookingRoom,
  BookingAvailabilityParams,
} from "../bookings.types";

export function useAvailableBookingRooms(
  params?: BookingAvailabilityParams,
  endpoint = "/bookings/available-rooms",
) {
  const page = usePage<any>();
  const [rooms, setRooms] = useState<AvailableBookingRoom[]>(
    page.props?.availableRooms ?? [],
  );
  const [isLoading, setIsLoading] = useState(false);
  const [isError, setIsError] = useState(false);
  const [requestId, setRequestId] = useState(0);

  useEffect(() => {
    if (!params) {
      setRooms(page.props?.availableRooms ?? []);
      setIsLoading(false);
      setIsError(false);
      return;
    }

    const controller = new AbortController();
    const query = new URLSearchParams({
      checkInDate: params.checkInDate,
      checkOutDate: params.checkOutDate,
      adults: String(params.adults),
      children: String(params.children),
    });

    setIsLoading(true);
    setIsError(false);

    fetch(`${endpoint}?${query}`, {
      headers: {
        Accept: "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
      signal: controller.signal,
    })
      .then(async (response) => {
        if (!response.ok)
          throw new Error("Unable to load available rooms.");
        const result = await response.json();
        setRooms(result.data ?? []);
      })
      .catch((error: unknown) => {
        if (
          error instanceof DOMException &&
          error.name === "AbortError"
        )
          return;
        setRooms([]);
        setIsError(true);
      })
      .finally(() => {
        if (!controller.signal.aborted) setIsLoading(false);
      });

    return () => controller.abort();
  }, [
    params?.checkInDate,
    params?.checkOutDate,
    params?.adults,
    params?.children,
    endpoint,
    page.props?.availableRooms,
    requestId,
  ]);

  return {
    rooms,
    isLoading,
    isError,
    refetch: () => setRequestId((current) => current + 1),
  };
}
