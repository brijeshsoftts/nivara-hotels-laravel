import { router } from "@inertiajs/react";
import { useState } from "react";
import { Banknote } from "lucide-react";
import { toast } from "sonner";

import { Button } from "@/components/ui/button";
import { Spinner } from "@/components/ui/spinner";
import { formatPrice } from "@/lib/format";
import { notifyError } from "@/lib/notification";

export function ManualCashPaymentButton({
  invoiceId,
  amount,
  onRecorded,
}: {
  invoiceId: string;
  amount: number;
  onRecorded: () => void;
}) {
  const [isPending, setIsPending] = useState(false);

  const handlePayment = () => {
    setIsPending(true);
    router.post("/dashboard/payments/cash", { invoiceId, amount }, {
      onSuccess: () => {
        toast.success("Cash payment recorded.");
        onRecorded();
      },
      onError: (err) => {
        notifyError(err);
      },
      onFinish: () => setIsPending(false),
    });
  };

  return (
    <Button
      className="w-full justify-center gap-2"
      disabled={isPending}
      onClick={handlePayment}
      type="button"
      variant="secondary"
    >
      {isPending ? <Spinner className="size-4" /> : <Banknote className="size-4" />}
      <span>Record Cash Payment ({formatPrice(amount)})</span>
    </Button>
  );
}
