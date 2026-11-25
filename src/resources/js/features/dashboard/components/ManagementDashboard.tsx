import { formatDate } from "date-fns/format";
import { useState } from "react";
import { usePage } from "@inertiajs/react";

import { type ColumnDef, DataTable } from "@/components/common/DataTable";
import {
    BookingBadge,
    PaymentStatusBadge,
} from "@/components/common/EnumBadges";
import { BookingDetailsSheet } from "@/features/bookings/components/BookingDetailsSheet";

import type { ArrivalItem, DepartureItem, Stays } from "../dashboard.types";
import { DashboardStats } from "./DashboardStats";
import { type BookingStatusRange, DonutChart } from "./DonutChart";
import { type Range, TrendChart } from "./TrendChart";
import { useDashboardBookingStatus } from "../hooks/useDashboardBookingStatus";

function Arrivals({ arrivals }: { arrivals: Stays["arrivals"] }) {
    const columns: ColumnDef<ArrivalItem>[] = [
        {
            key: "booking",
            header: "Booking",
            cell: (row) => <>{row.bookingReference}</>,
        },
        {
            key: "customer",
            header: "Customer",
            cell: (row) => <>{row.customerName}</>,
        },
        {
            key: "room",
            header: "Room",
            cell: (row) => row.roomNumber ?? "-",
        },
        {
            key: "checkin",
            header: "Check In",
            cell: (row) => formatDate(row.checkIn, "dd MMM, yyyy"),
        },
        {
            key: "status",
            header: "Status",
            cell: (row) => <BookingBadge value={row.bookingStatus} />,
        },
    ];

    const [selectedBookingId, setSelectedBookingId] = useState<string | null>(
        null,
    );

    return (
        <>
            <DataTable
                title="Today's Arrivals"
                description={`Guests scheduled to check in today - ${arrivals?.total ?? 0} total`}
                data={arrivals?.items ?? []}
                columns={columns}
                showSearch={false}
                showPagination={false}
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
        </>
    );
}

function Departures({ departures }: { departures: Stays["departures"] }) {
    const columns: ColumnDef<DepartureItem>[] = [
        {
            key: "booking",
            header: "Booking",
            cell: (row) => <>{row.bookingReference}</>,
        },
        {
            key: "customer",
            header: "Customer",
            cell: (row) => <>{row.customerName}</>,
        },
        {
            key: "room",
            header: "Room",
            cell: (row) => row.roomNumber ?? "-",
        },
        {
            key: "checkout",
            header: "Check Out",
            cell: (row) => formatDate(row.checkOut, "dd MMM, yyyy"),
        },
        {
            key: "paymentStatus",
            header: "Payment Status",
            cell: (row) => <PaymentStatusBadge value={row.paymentStatus} />,
        },
        {
            key: "outstandingBalance",
            header: "Outstanding Balance",
            cell: (row) => row.outstandingBalance.toLocaleString(),
        },
    ];

    const [selectedBookingId, setSelectedBookingId] = useState<string | null>(
        null,
    );

    return (
        <DataTable
            title="Today's departures"
            description={`Guests scheduled to check out today - ${departures?.total ?? 0} total`}
            data={departures?.items ?? []}
            columns={columns}
            showSearch={false}
            showPagination={false}
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

function RevenueTrend({ data: initialData }: { data?: any[] }) {
    const [range, setRange] = useState<Range>("7d");
    const data = (initialData ?? []).slice(-Number.parseInt(range, 10));

    return (
        <TrendChart
            data={data}
            range={range}
            onRangeChange={setRange}
            isLoading={false}
            isError={false}
            onRetry={() => {}}
            title="Revenue trend"
            description="Successful payments collected per day."
            options={[
                {
                    label: "7 days",
                    value: "7d",
                },
                {
                    label: "14 days",
                    value: "14d",
                },
                {
                    label: "28 days",
                    value: "28d",
                },
            ]}
        />
    );
}

function BookingStatus({
    data: initialData = [],
}: {
    data?: { status: string; count: number }[];
}) {
    const [range, setRange] = useState<BookingStatusRange>("all");
    const { data, isLoading, isError, refetch } = useDashboardBookingStatus(
        range,
        initialData,
    );

    return (
        <DonutChart
            data={data}
            range={range}
            onRangeChange={setRange}
            isLoading={isLoading}
            isError={isError}
            onRetry={refetch}
            title="Booking status distribution"
            description="Bookings created in the selected period, grouped by status."
            centerLabel="Total"
        />
    );
}

export function ManagementDashboard({
    stats,
    stays: propStays,
    revenueTrend: propRevenueTrend,
    bookingStatus: propBookingStatus,
}: {
    stats?: any[];
    stays?: any;
    revenueTrend?: any[];
    bookingStatus?: any;
} = {}) {
    const page = usePage<any>();
    const stays = propStays ??
        page.props?.stays ?? {
            arrivals: { total: 0, items: [] },
            departures: { total: 0, items: [] },
        };
    const revenueTrend = propRevenueTrend ?? page.props?.revenueTrend ?? [];
    const bookingStatus = propBookingStatus ?? page.props?.bookingStatus ?? [];

    return (
        <div className="space-y-6">
            <DashboardStats stats={stats} />
            <div className="grid items-start gap-6 lg:grid-cols-2">
                <RevenueTrend data={revenueTrend} />
                <BookingStatus data={bookingStatus} />
            </div>
            <div className="grid gap-6 lg:grid-cols-2">
                <Arrivals arrivals={stays.arrivals} />
                <Departures departures={stays.departures} />
            </div>
        </div>
    );
}
