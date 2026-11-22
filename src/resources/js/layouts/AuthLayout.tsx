import {
  BedDouble,
  CalendarDays,
  ChartNoAxesCombined,
  DoorOpen,
  Heart,
  Tag,
  UsersRound,
} from "lucide-react";
import { Link, usePage } from "@inertiajs/react";
import type { SharedProps } from "@/types";

import { Logo } from "@/layouts/PublicLayout";
import { PageTransition } from "@/components/common/PageTransition";
import { HERO_IMAGE } from "@/features/public/public.mock";

export function AuthLayout({ children }: { children?: React.ReactNode } = {}) {
  const { auth } = usePage<SharedProps>().props;
  const { url } = usePage();
  const pathname = url ? url.split("?")[0] : "";
  const isLoginPage = pathname === "/login";
  const isRegisterPage = pathname === "/register";
  const isBrandedAuthPage = isLoginPage || isRegisterPage;

  return (
    <main className="grid min-h-svh bg-background text-foreground lg:grid-cols-2">
      <section
        aria-label="Nivara Hotels"
        className={`relative isolate hidden min-h-svh flex-col overflow-hidden bg-muted px-8 py-8 text-background lg:flex lg:px-12 lg:py-10 ${isBrandedAuthPage ? "lg:justify-start" : "lg:justify-between"
          }`}
      >
        <img
          alt="A Nivara hotel and resort"
          className="absolute inset-0 -z-20 size-full object-cover"
          fetchPriority="high"
          src={HERO_IMAGE}
        />
        <div className="absolute inset-0 -z-10 bg-foreground/55" />
        <Logo className="relative z-10" inverse />
        {isLoginPage ? (
          <div className="relative z-10 mt-10 max-w-xl xl:mt-12">
            <h1 className="max-w-md font-heading text-4xl leading-[1.08] font-medium text-background xl:text-5xl">
              <span className="block">Manage Your</span>{" "}
              <span className="block">Hotel with Ease</span>
            </h1>
            <p className="mt-4 max-w-lg text-base leading-6 text-background/85">
              Secure access to your hotel management dashboard.
            </p>
            <div
              aria-hidden="true"
              className="mt-7 h-px w-16 bg-brand-accent"
            />
            <ul className="mt-6 space-y-3">
              {[
                { icon: BedDouble, label: "Manage bookings" },
                { icon: DoorOpen, label: "Control rooms" },
                { icon: UsersRound, label: "Guest management" },
                { icon: ChartNoAxesCombined, label: "Reports & analytics" },
              ].map(({ icon: Icon, label }) => (
                <li
                  className="flex items-center gap-3 text-sm text-background/90"
                  key={label}
                >
                  <span className="grid size-9 place-items-center rounded-full bg-background/10 text-brand-accent">
                    <Icon
                      aria-hidden="true"
                      className="size-5"
                      strokeWidth={1.8}
                    />
                  </span>
                  {label}
                </li>
              ))}
            </ul>
          </div>
        ) : isRegisterPage ? (
          <div className="relative z-10 mt-10 max-w-xl xl:mt-12">
            <h1 className="max-w-md font-heading text-4xl leading-[1.08] font-medium text-background xl:text-5xl">
              <span className="block">Create Your</span>{" "}
              <span className="block">Account</span>
            </h1>
            <p className="mt-4 max-w-lg text-base leading-6 text-background/85">
              Register to manage your stays and make your next visit easier.
            </p>
            <div
              aria-hidden="true"
              className="mt-7 h-px w-16 bg-brand-accent"
            />
            <ul className="mt-6 space-y-3">
              {[
                { icon: BedDouble, label: "Book your stay" },
                { icon: Tag, label: "Explore room offers" },
                { icon: CalendarDays, label: "Manage bookings" },
                { icon: Heart, label: "Save favorite rooms" },
              ].map(({ icon: Icon, label }) => (
                <li
                  className="flex items-center gap-3 text-sm text-background/90"
                  key={label}
                >
                  <span className="grid size-9 place-items-center rounded-full bg-background/10 text-brand-accent">
                    <Icon
                      aria-hidden="true"
                      className="size-5"
                      strokeWidth={1.8}
                    />
                  </span>
                  {label}
                </li>
              ))}
            </ul>
          </div>
        ) : null}
      </section>

      <section className="flex min-h-svh flex-col justify-between px-6 py-8 sm:px-10 lg:px-12">
        <div className="flex items-center justify-between lg:hidden">
          <Logo />
        </div>
        <div className="mx-auto flex w-full max-w-md flex-1 items-center justify-center py-8">
          <PageTransition>{children}</PageTransition>
        </div>
        <footer className="text-center text-xs text-muted-foreground">
          &copy; {new Date().getFullYear()} Nivara Hotels &amp; Resorts. All rights reserved.
        </footer>
      </section>
    </main>
  );
}

export default AuthLayout;
