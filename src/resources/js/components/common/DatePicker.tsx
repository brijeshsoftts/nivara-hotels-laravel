import { format, isValid, parseISO } from "date-fns";
import { CalendarDays } from "lucide-react";
import { useState } from "react";

import { Button } from "@/components/ui/button";
import { Calendar } from "@/components/ui/calendar";
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "@/components/ui/popover";
import { cn } from "@/lib/utils";

type DatePickerProps = {
  id?: string;
  value?: string;
  onChange: (value: string) => void;
  placeholder?: string;
  minDate?: Date;
  disabled?: boolean;
  className?: string;
  "aria-invalid"?: boolean;
};

export function DatePicker({
  id,
  value,
  onChange,
  placeholder = "Select date",
  minDate,
  disabled = false,
  className,
  "aria-invalid": ariaInvalid,
}: DatePickerProps) {
  const [open, setOpen] = useState(false);

  const parsed = value ? parseISO(value) : undefined;
  const selectedDate = parsed && isValid(parsed) ? parsed : undefined;
  const validMinDate = minDate && isValid(minDate) ? minDate : undefined;

  return (
    <Popover open={open} onOpenChange={(next) => !disabled && setOpen(next)}>
      <PopoverTrigger
        disabled={disabled}
        render={
          <Button
            id={id}
            type="button"
            variant="outline"
            disabled={disabled}
            aria-invalid={ariaInvalid}
            className={cn(
              "h-9 w-full justify-start gap-2 rounded-md border-input bg-background px-3 text-left text-sm font-normal text-foreground focus-visible:ring-2 focus-visible:ring-ring",
              !selectedDate && "text-muted-foreground",
              className
            )}
          />
        }
      >
        <CalendarDays
          className="size-4 shrink-0 text-primary"
          aria-hidden="true"
        />
        <span className="truncate">
          {selectedDate ? format(selectedDate, "dd MMM yyyy") : placeholder}
        </span>
      </PopoverTrigger>
      <PopoverContent align="start" className="w-auto rounded-lg p-0">
        <Calendar
          mode="single"
          selected={selectedDate}
          defaultMonth={selectedDate ?? validMinDate}
          onSelect={(date) => {
            if (!date) return;
            onChange(format(date, "yyyy-MM-dd"));
            setOpen(false);
          }}
          disabled={validMinDate ? { before: validMinDate } : undefined}
        />
      </PopoverContent>
    </Popover>
  );
}

