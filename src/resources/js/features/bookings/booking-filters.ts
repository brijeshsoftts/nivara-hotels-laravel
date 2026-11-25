const bookingStatuses: string[] = [
    "PENDING",
    "CONFIRMED",
    "CHECKED_IN",
    "CHECKED_OUT",
    "CANCELLED",
    "NO_SHOW",
    "NON_CANCELLED",
];

const invoiceStatuses = ["UNPAID", "PARTIALLY_PAID", "PAID", "REFUNDED"];
const paymentMethods = ["CASH", "ONLINE"];

export const bookingFilters = [
    {
        key: "status",
        label: "Booking status",
        options: bookingStatuses.map((value) => ({
            value,
            label: value === "NON_CANCELLED" ? "Non-cancelled" : value,
        })),
    },
    {
        key: "invoiceStatus",
        label: "Invoice status",
        options: invoiceStatuses.map((status) => ({
            label: status,
            value: status,
        })),
    },
    {
        key: "paymentMethod",
        label: "Payment method",
        options: paymentMethods.map((method) => ({
            label: method,
            value: method,
        })),
    },
];
