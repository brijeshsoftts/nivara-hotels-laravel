import { formatDate } from "date-fns";
import { useState } from "react";
import { usePage } from "@inertiajs/react";

import { type ColumnDef, DataTable } from "@/components/common/DataTable";
import {
  BookingBadge,
  PaymentStatusBadge,
} from "@/components/common/EnumBadges";
import { StatGrid } from "@/components/common/Stat";
import { BookingDetailsSheet } from "@/features/bookings/components/BookingDetailsSheet";

import type { ArrivalItem, DepartureItem } from "../dashboard.types";
import { DonutChart } from "./DonutChart";
import { StatusBarChart } from "./StatusBarChart";

function TodaysArrivals({
  items,
}: {
  items: ArrivalItem[];
}) {
  const [selectedBookingId, setSelectedBookingId] = useState<string | null>(
    null
  );
  const columns: ColumnDef<ArrivalItem>[] = [
    { key: "bookingReference", header: "Booking" },
    { key: "customerName", header: "Customer" },
    {
      key: "roomNumber",
      header: "Room",
      cell: (row) => row.roomNumber ?? "Unassigned",
    },
    {
      key: "checkIn",
      header: "Check In",
      cell: (row) => formatDate(row.checkIn, "dd MMM, yyyy"),
    },
    {
      key: "bookingStatus",
      header: "Status",
      cell: (row) => <BookingBadge value={row.bookingStatus} />,
    },
  ];

  return (
    <DataTable
      title="Today's Arrivals"
      description="Guests scheduled to check in today"
      data={items}
      columns={columns}
      showSearch={false}
      showPagination={false}
      isLoading={false}
      isError={false}
      errorMessage="Today's arrivals could not be loaded."
      emptyMessage="No arrivals scheduled for today."
      onRowClick={(row) => setSelectedBookingId(row.bookingId)}
      renderActions={(row) =>
        selectedBookingId === row.bookingId ? (
          <BookingDetailsSheet
            onOpenChange={() => setSelectedBookingId(null)}
            open
            bookingId={row.bookingId}
          />
        ) : null
      }
    />
  );
}

function TodaysDepartures({
  items,
}: {
  items: DepartureItem[];
}) {
  const [selectedBookingId, setSelectedBookingId] = useState<string | null>(
    null
  );
  const columns: ColumnDef<DepartureItem>[] = [
    { key: "bookingReference", header: "Booking" },
    { key: "customerName", header: "Customer" },
    {
      key: "roomNumber",
      header: "Room",
      cell: (row) => row.roomNumber ?? "Unassigned",
    },
    {
      key: "checkOut",
      header: "Check Out",
      cell: (row) => formatDate(row.checkOut, "dd MMM, yyyy"),
    },
    {
      key: "paymentStatus",
      header: "Payment",
      cell: (row) => <PaymentStatusBadge value={row.paymentStatus} />,
    },
  ];

  return (
    <DataTable
      title="Today's Departures"
      description="Guests scheduled to check out today"
      data={items}
      columns={columns}
      showSearch={false}
      showPagination={false}
      isLoading={false}
      isError={false}
      errorMessage="Today's departures could not be loaded."
      emptyMessage="No departures scheduled for today."
      onRowClick={(row) => setSelectedBookingId(row.bookingId)}
      renderActions={(row) =>
        selectedBookingId === row.bookingId ? (
          <BookingDetailsSheet
            onOpenChange={() => setSelectedBookingId(null)}
            open
            bookingId={row.bookingId}
          />
        ) : null
      }
    />
  );
}

export function ReceptionistDashboard({
  stats: propStats,
  stays: propStays,
  roomStatus: propRoomStatus,
  bookingStatus: propBookingStatus,
}: {
  stats?: any[];
  stays?: any;
  roomStatus?: any;
  bookingStatus?: any;
} = {}) {
  const page = usePage<any>();
  const stats = propStats ?? page.props?.stats ?? [];
  const stays = propStays ?? page.props?.stays ?? { arrivals: { items: [] }, departures: { items: [] } };
  const roomStatus = propRoomStatus ?? page.props?.roomStatus ?? [];
  const bookingStatus = propBookingStatus ?? page.props?.bookingStatus ?? [];

  return (
    <div className="space-y-6">
      <StatGrid
        stats={stats}
        isLoading={false}
        isError={false}
      />
      <div className="grid gap-6 lg:grid-cols-2">
        <DonutChart
          data={Array.isArray(roomStatus) ? roomStatus : []}
          title="Room Availability"
          description="Current active room occupancy"
          centerLabel="Rooms"
          showRange={false}
          errorMessage="Room availability could not be loaded."
          isLoading={false}
          isError={false}
        />
        <StatusBarChart
          data={Array.isArray(bookingStatus) ? bookingStatus : []}
          title="Booking pipeline"
          description="Total active and upcoming reservations"
          isLoading={false}
          isError={false}
        />
      </div>
      <div className="grid gap-6 lg:grid-cols-2">
        <TodaysArrivals items={stays.arrivals?.items ?? []} />
        <TodaysDepartures items={stays.departures?.items ?? []} />
      </div>
    </div>
  );
}
