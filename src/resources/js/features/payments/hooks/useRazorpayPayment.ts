import { router } from "@inertiajs/react";
import { useRef, useState } from "react";
import { type RazorpayOrderOptions, useRazorpay } from "react-razorpay";
import { toast } from "sonner";

import { notifyError } from "@/lib/notification";
import type { PaymentItem, PublicPaymentStatus } from "../payments.types";

type PaymentCheckoutContext = {
  paymentId?: string;
  checkoutToken?: string;
  onStatus?: (status: PaymentItem | PublicPaymentStatus) => void;
  onCancelled?: () => void;
  onFailed?: () => void;
  onVerificationFailed?: () => void;
};

export type { PaymentCheckoutContext };

export function useRazorpayPayment() {
  const { error: scriptError, isLoading, Razorpay } = useRazorpay();
  const isRazorpayAvailable =
    typeof window !== "undefined" && "Razorpay" in window;
  const isScriptLoading = isLoading && !isRazorpayAvailable;
  const [isVerifying, setIsVerifying] = useState(false);
  const [isCheckoutOpen, setIsCheckoutOpen] = useState(false);
  const [isOrdering, setIsOrdering] = useState(false);
  const paymentLock = useRef(false);

  const postJson = async (url: string, data: any) => {
    const xsrfCookie = document.cookie
      .split("; ")
      .find((cookie) => cookie.startsWith("XSRF-TOKEN="))
      ?.slice("XSRF-TOKEN=".length);
    const headers: Record<string, string> = {
      "Content-Type": "application/json",
      Accept: "application/json",
      "X-Requested-With": "XMLHttpRequest",
    };
    if (xsrfCookie)
      headers["X-XSRF-TOKEN"] = decodeURIComponent(xsrfCookie);

    const res = await fetch(url, {
      method: "POST",
      headers,
      body: JSON.stringify(data),
    });
    if (!res.ok) {
      const err = await res
        .json()
        .catch(() => ({ message: "Request failed" }));
      throw err;
    }
    const json = await res.json();
    return json.data ?? json;
  };

  const reconcile = async (
    paymentId: string,
    context?: PaymentCheckoutContext,
    quietPending = false,
  ) => {
    try {
      let payment: any;
      if (context?.checkoutToken) {
        payment = await postJson("/payments/public/status", {
          paymentId,
          checkoutToken: context.checkoutToken,
        });
      } else {
        const res = await fetch(`/payments/${paymentId}`, {
          headers: { Accept: "application/json" },
        });
        const json = await res.json();
        payment = json.data ?? json;
      }

      router.reload();
      context?.onStatus?.(payment);

      const status = payment.paymentStatus;
      const bookingConfirmed =
        !("bookingStatus" in payment) ||
        payment.bookingStatus === "CONFIRMED";
      if (status === "COMPLETED" && bookingConfirmed) {
        toast.success(
          "bookingStatus" in payment
            ? "Booking Confirmed! Your reservation has been successfully confirmed."
            : "Payment confirmed.",
        );
      } else if (status === "COMPLETED") {
        toast.message("Verifying your booking. Please wait...");
      } else if (status === "FAILED") {
        toast.error(
          "Payment Failed. Your payment could not be completed. Please try again.",
        );
      } else if (!quietPending) {
        toast.message(
          "Payment is still being confirmed. Check back shortly.",
        );
      }
    } catch (error) {
      notifyError(error);
    }
  };

  const startPayment = async (
    bookingId: string,
    bookingReference?: string,
    context?: PaymentCheckoutContext,
  ) => {
    if (scriptError) {
      toast.error("Checkout could not be loaded. Please try again.");
      return;
    }

    if (isScriptLoading) return;
    if (paymentLock.current) return;
    paymentLock.current = true;
    setIsOrdering(true);

    try {
      const order = context?.checkoutToken
        ? await postJson("/payments/public/orders", {
          bookingId,
          checkoutToken: context.checkoutToken,
        })
        : await postJson("/payments/orders", {
          bookingId,
          paymentId: context?.paymentId,
        });

      let verificationStarted = false;
      let failureHandled = false;

      const options: RazorpayOrderOptions = {
        key: order.keyId,
        amount: order.amount,
        currency: order.currency,
        name: "Nivara Hotels",
        description: bookingReference
          ? `Booking ${bookingReference}`
          : "Booking payment",
        order_id: order.orderId,
        modal: {
          ondismiss: () => {
            paymentLock.current = false;
            setIsCheckoutOpen(false);
            if (!verificationStarted) {
              if (!failureHandled) context?.onCancelled?.();
              void reconcile(order.paymentId, context, true);
            }
          },
        },
        handler: async (response) => {
          if (verificationStarted) return;
          verificationStarted = true;
          paymentLock.current = false;
          setIsCheckoutOpen(false);
          setIsVerifying(true);

          try {
            const verificationInput = {
              paymentId: order.paymentId,
              razorpayPaymentId: response.razorpay_payment_id,
              razorpayOrderId: response.razorpay_order_id,
              razorpaySignature: response.razorpay_signature,
            };

            const payment = context?.checkoutToken
              ? await postJson("/payments/public/verify", {
                ...verificationInput,
                checkoutToken: context.checkoutToken,
              })
              : await postJson(
                "/payments/verify",
                verificationInput,
              );

            router.reload();
            context?.onStatus?.(payment);

            const status = payment.paymentStatus;
            const bookingConfirmed =
              !("bookingStatus" in payment) ||
              payment.bookingStatus === "CONFIRMED";
            if (status === "COMPLETED" && bookingConfirmed) {
              toast.success(
                "bookingStatus" in payment
                  ? "Booking Confirmed! Your reservation has been successfully confirmed."
                  : "Payment confirmed.",
              );
            } else if (status === "COMPLETED") {
              toast.message(
                "Verifying your booking. Please wait...",
              );
            } else {
              toast.message(
                "Verifying your payment. Please wait...",
              );
            }
          } catch {
            context?.onVerificationFailed?.();
            toast.error(
              "Payment Verification Failed. We couldn't verify your payment. Please contact support if money was deducted.",
            );
            await reconcile(order.paymentId, context, true);
          } finally {
            setIsVerifying(false);
          }
        },
      };

      const checkout = new Razorpay(options);
      checkout.on("payment.failed", () => {
        failureHandled = true;
        context?.onFailed?.();
        toast.error(
          "Payment Failed. Your payment could not be completed. Please try again.",
        );
        void reconcile(order.paymentId, context, true);
      });
      setIsCheckoutOpen(true);
      checkout.open();
    } catch (error) {
      paymentLock.current = false;
      setIsCheckoutOpen(false);
      notifyError(error);
    } finally {
      setIsOrdering(false);
    }
  };

  return {
    startPayment,
    isPending: isScriptLoading || isOrdering || isVerifying,
    isCheckoutOpen,
  };
}
