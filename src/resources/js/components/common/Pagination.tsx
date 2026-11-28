import { ChevronLeft, ChevronRight } from "lucide-react";

import { Button } from "@/components/ui/button";
import type { Pagination } from "@/types/api.types";

interface PaginationProps {
  pagination?: Pagination;
  onPageChange: (page: number) => void;
}
export function Pagination({ pagination, onPageChange }: PaginationProps) {
  const {
    page: currentPage,
    limit: pageSize,
    total: totalRecords,
  } = pagination || {
    page: 0,
    limit: 10,
    total: 0,
  }; // Calculate total pages from API response.
  const totalPages = Math.ceil(totalRecords / pageSize); // Calculate visible record range.
  const startRecord = totalRecords === 0 ? 0 : (currentPage - 1) * pageSize + 1;
  const endRecord = Math.min(currentPage * pageSize, totalRecords);
  const isFirstPage = currentPage <= 1;
  const isLastPage = currentPage >= totalPages || totalPages === 0;
  const handlePrevious = () => {
    if (!isFirstPage) {
      onPageChange(currentPage - 1);
    }
  };
  const handleNext = () => {
    if (!isLastPage) {
      onPageChange(currentPage + 1);
    }
  }; // Don't render pagination when there are no records.

  return (
    <div className="flex flex-col gap-4 border-t border-border/60 bg-card px-4 py-4 sm:px-6 md:flex-row md:items-center md:justify-between">
      {/* ───────────────── Record information ───────────────── */}
      <div className="text-center text-xs text-muted-foreground md:text-left">
        Showing{" "}
        <span className="font-medium text-foreground">
          {startRecord}–{endRecord}
        </span>{" "}
        of <span className="font-medium text-foreground"> {totalRecords} </span>
        records
      </div>
      {/* ───────────────── Pagination controls ───────────────── */}
      <div className="flex items-center justify-center gap-2">
        {/* Previous */}
        <Button
          type="button"
          variant="outline"
          size="sm"
          disabled={isFirstPage}
          onClick={handlePrevious}
          className="h-9 gap-1.5 px-3 text-xs transition-all"
          aria-label="Go to previous page"
        >
          <ChevronLeft className="size-4" /> <span>Previous</span>
        </Button>
        {/* Page numbers */}
        {totalPages > 0 && (
          <div className="flex items-center gap-1" aria-label="Page navigation">
            {Array.from({ length: totalPages }, (_, i) => i + 1)
              .filter(
                (page) =>
                  page === 1 ||
                  page === totalPages ||
                  Math.abs(page - currentPage) <= 1
              )
              .reduce<(number | string)[]>((acc, page, index, arr) => {
                if (index > 0 && page - (arr[index - 1] as number) > 1) {
                  acc.push("...");
                }
                acc.push(page);
                return acc;
              }, [])
              .map((page, idx) =>
                typeof page === "number" ? (
                  <Button
                    key={page}
                    type="button"
                    variant={currentPage === page ? "default" : "outline"}
                    size="sm"
                    onClick={() => onPageChange(page)}
                    aria-label={`Go to page ${page}`}
                    aria-current={currentPage === page ? "page" : undefined}
                    className="h-9 min-w-9 px-2.5 text-xs transition-all"
                  >
                    {page}
                  </Button>
                ) : (
                  <span
                    key={`ellipsis-${idx}`}
                    className="px-1 text-xs text-muted-foreground select-none"
                  >
                    {page}
                  </span>
                )
              )}
          </div>
        )}
        {/* Next */}
        <Button
          type="button"
          variant="outline"
          size="sm"
          disabled={isLastPage}
          onClick={handleNext}
          className="h-9 gap-1.5 px-3 text-xs transition-all"
          aria-label="Go to next page"
        >
          <span>Next</span> <ChevronRight className="size-4" />
        </Button>
      </div>
    </div>
  );
}
