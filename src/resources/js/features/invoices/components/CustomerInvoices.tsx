import { formatDate } from "date-fns";
import { CreditCard, EllipsisVertical, Eye } from "lucide-react";
import { useState } from "react";

import { type ColumnDef, DataTable } from "@/components/common/DataTable";
import { InvoiceBadge } from "@/components/common/EnumBadges";
import { IconBtn } from "@/components/common/IconBtn";
import { StatGrid } from "@/components/common/Stat";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { useListParams } from "@/hooks/useListParams";
import { formatPrice } from "@/lib/format";

import { useRazorpayPayment } from "@/features/payments/hooks/useRazorpayPayment";

import { useInvoicesStats } from "../hooks/useInvoicesStats";
import { useInvoices } from "../hooks/useInvoices";
import type { CustomerInvoice } from "../invoices.types";

import { InvoiceDownload } from "./InvoiceDownload";
import { filters } from "./ManagementInvoices";
import { ViewInvoiceDetails } from "./ViewInvoiceDetails";

function Stats() {
    const { data, isLoading, isError } = useInvoicesStats();
    return <StatGrid stats={data} isLoading={isLoading} isError={isError} />;
}

function InvoicesTable() {
    const columns: ColumnDef<CustomerInvoice>[] = [
        {
            key: "invoiceNumber",
            header: "Invoice #",
            className: "min-w-36",
            cell: (invoice) => (
                <span className="font-medium">{invoice.invoiceNumber}</span>
            ),
        },
        {
            key: "invoiceDate",
            header: "Invoice Date",
            cell: (invoice) =>
                invoice.invoiceDate
                    ? formatDate(invoice.invoiceDate, "dd MMM, yyyy")
                    : "—",
        },
        {
            key: "bookingReference",
            header: "Booking Reference",
            className: "min-w-40",
            cell: (invoice) => (
                <span className="font-medium">{invoice.bookingReference}</span>
            ),
        },
        {
            key: "totalAmount",
            header: "Total Amount",
            align: "right",
            cell: (invoice) => (
                <span className="font-semibold tabular-nums">
                    {formatPrice(invoice.totalAmount)}
                </span>
            ),
        },
        {
            key: "balanceDue",
            header: "Balance Due",
            align: "right",
            cell: (invoice) => (
                <span
                    className={`font-medium tabular-nums ${
                        invoice.balanceDue > 0
                            ? "text-destructive"
                            : "text-muted-foreground"
                    }`}
                >
                    {formatPrice(invoice.balanceDue)}
                </span>
            ),
        },
        {
            key: "dueDate",
            header: "Due Date",
            cell: (invoice) =>
                invoice.dueDate
                    ? formatDate(invoice.dueDate, "dd MMM, yyyy")
                    : "—",
        },
        {
            key: "status",
            header: "Status",
            cell: (invoice) => (
                <span className="">
                    <InvoiceBadge value={invoice.status} />
                </span>
            ),
        },

        {
            key: "actions",
            header: "Actions",
            cell: (invoice) => <ActionDropdownMenu invoice={invoice} />,
        },
    ];

    const { setSearch, setPage, params, setFilter } = useListParams();
    const { items, pagination, isLoading, isError, refetch } =
        useInvoices<CustomerInvoice>(params);

    return (
        <DataTable
            title="Invoices"
            description="Review invoices"
            data={items}
            columns={columns}
            searchPlaceholder="Search invoice, booking, or customer..."
            searchableKeys={[
                "invoiceNumber",
                "bookingReference",
                "customerName",
            ]}
            onSearchChange={setSearch}
            filters={filters}
            onFilterChange={(key, value) => setFilter({ [key]: value })}
            onPageChange={setPage}
            pagination={pagination}
            isLoading={isLoading}
            isError={isError}
            refetch={refetch}
            errorMessage="Could not load invoices. Please try again."
            emptyMessage="No invoices match your search or filters."
            showPagination
        />
    );
}

function ActionDropdownMenu({ invoice }: { invoice: CustomerInvoice }) {
    const [isViewOpen, setIsViewOpen] = useState(false);
    const { startPayment, isPending } = useRazorpayPayment();

    return (
        <>
            <DropdownMenu>
                <DropdownMenuTrigger>
                    <IconBtn
                        size="sm"
                        variant="ghost"
                        shape="square"
                        aria-label={`Actions for ${invoice.id}`}
                    >
                        <EllipsisVertical />
                    </IconBtn>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" className=" ">
                    <DropdownMenuItem onClick={() => setIsViewOpen(true)}>
                        <Eye />
                        View
                    </DropdownMenuItem>

                    <DropdownMenuItem
                        disabled={
                            invoice.balanceDue <= 0 ||
                            invoice.status === "REFUNDED" ||
                            isPending
                        }
                        onClick={() =>
                            void startPayment(
                                invoice.bookingId,
                                invoice.bookingReference,
                            )
                        }
                    >
                        <CreditCard />
                        Pay
                    </DropdownMenuItem>
                    <DropdownMenuItem>
                        <InvoiceDownload
                            invoice={{
                                id: invoice.id,
                                invoiceNumber: invoice.invoiceNumber,
                                customerName: invoice.customerName,
                                bookingReference: invoice.bookingReference,
                                totalAmount: invoice.totalAmount,
                                paidAmount: invoice.paidAmount,
                                balanceDue: invoice.balanceDue,
                                status: invoice.status,
                                issuedAt: invoice.invoiceDate,
                            }}
                        />
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
            {isViewOpen && (
                <ViewInvoiceDetails
                    id={invoice.id}
                    open={isViewOpen}
                    onOpenChange={setIsViewOpen}
                />
            )}
        </>
    );
}

export function CustomerInvoices() {
    return (
        <>
            <Stats />
            <InvoicesTable />
        </>
    );
}
