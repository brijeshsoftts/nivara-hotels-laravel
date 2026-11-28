import {
    ArrowUpRight,
    Calendar,
    LayoutDashboard,
    Mail,
    MapPin,
    Menu,
    Palmtree,
    Phone,
    X,
} from "lucide-react";
import { AnimatePresence, motion, useReducedMotion } from "motion/react";
import { useEffect, useState } from "react";
import { Link, usePage } from "@inertiajs/react";

// import { PageTransition } from "@/components/common/PageTransition";
import { Button } from "@/components/ui/button";
import { Separator } from "@/components/ui/separator";
import { HOTEL_DETAILS } from "@/constants/hotel";
import {
    getBookingPreferences,
    publicPageUrl,
    saveBookingPreferences,
} from "@/features/public/bookingPreferences";
import { useAuth } from "@/hooks/useAuth";
import { cn } from "@/lib/utils";

const navLinks = [
    { name: "Home", href: "/" },
    { name: "About", href: "/about" },
    { name: "Rooms", href: "/rooms" },
    { name: "Contact", href: "/contact" },
];

export function Logo({
    to = "/",
    href,
    className,
    inverse = false,
    compact = false,
}: {
    to?: string;
    href?: string;
    className?: string;
    inverse?: boolean;
    compact?: boolean;
}) {
    return (
        <Link
            href={publicPageUrl(href ?? to)}
            className={cn(
                "flex items-center gap-3",
                compact && "gap-2",
                className,
            )}
        >
            {/* Logo Mark - Arched Background matching screenshot */}
            <div
                className={cn(
                    "relative flex items-end justify-center overflow-hidden",
                    compact
                        ? "size-10 rounded-md bg-sidebar-accent"
                        : "h-12 w-10 rounded-t-full rounded-b-sm bg-secondary pb-1",
                )}
            >
                <Palmtree
                    className={cn(
                        "absolute",
                        compact
                            ? "bottom-0 size-7 text-primary not-dark:text-background"
                            : "-bottom-1 h-7 w-7 text-primary",
                    )}
                    strokeWidth={1.5}
                />
            </div>

            {/* Logo Text */}
            <div className="flex min-w-0 flex-col justify-center">
                <span
                    className={cn(
                        "font-heading leading-none tracking-wide",
                        compact
                            ? "text-lg text-sidebar-foreground"
                            : "text-[26px]",
                        !compact &&
                        (inverse ? "dark:text-primary  text-background" : "text-primary"),
                    )}
                >
                    Nivara
                </span>
                {!compact && (
                    <span
                        className={cn(
                            "mt-1 font-sans text-[9px] font-medium tracking-[0.25em] uppercase",
                            inverse ? "dark:text-primary  text-background" : "text-primary/80",
                        )}
                    >
                        Hotel &amp; Resort
                    </span>
                )}
            </div>
        </Link>
    );
}

function Navbar() {
    const [isMenuOpen, setIsMenuOpen] = useState(false);
    const reduceMotion = useReducedMotion() ?? false;
    const { isAuthenticated, isLoading } = useAuth();
    const { url } = usePage();
    const pathname = url ? url.split("?")[0] : "/";
    const closeMenu = () => setIsMenuOpen(false);

    useEffect(() => {
        if (!isMenuOpen) return;
        const closeOnEscape = (event: KeyboardEvent) => {
            if (event.key === "Escape") setIsMenuOpen(false);
        };
        window.addEventListener("keydown", closeOnEscape);
        return () => window.removeEventListener("keydown", closeOnEscape);
    }, [isMenuOpen]);

    return (
        <header className="sticky top-0 z-40 border-b border-border/80 bg-background/95 text-foreground backdrop-blur-md supports-backdrop-filter:bg-background/85">
            <nav
                aria-label="Main navigation"
                className="container mx-auto flex min-h-18 w-full max-w-7xl items-center justify-between gap-5 px-4 sm:px-6 lg:px-8"
            >
                <Logo />
                <div className="hidden items-center gap-7 lg:flex xl:gap-9">
                    <nav className="hidden items-center gap-9 lg:flex">
                        {navLinks.map((item) => {
                            const isActive =
                                item.href === "/"
                                    ? pathname === "/"
                                    : pathname.startsWith(item.href);
                            return (
                                <Link
                                    key={item.href}
                                    href={publicPageUrl(item.href)}
                                    className={cn(
                                        "relative py-2 text-[13px] font-medium tracking-wide transition-colors duration-200",
                                        "after:absolute after:inset-x-0 after:-bottom-0.5 after:h-px after:origin-left after:bg-primary after:transition-transform after:duration-300",
                                        isActive
                                            ? "text-foreground after:scale-x-100"
                                            : "text-muted-foreground after:scale-x-0 hover:text-foreground hover:after:scale-x-100",
                                    )}
                                >
                                    {item.name}
                                </Link>
                            );
                        })}
                    </nav>
                </div>
                <div className="hidden items-center gap-4 lg:flex">
                    {!isLoading &&
                        (isAuthenticated ? (
                            <Button
                                nativeButton={false}
                                render={<Link href="/dashboard" />}
                                className="h-11 rounded-lg px-5 font-semibold"
                                variant="outline"
                            >
                                <LayoutDashboard
                                    aria-hidden="true"
                                    className="size-4"
                                />
                                Dashboard
                            </Button>
                        ) : (
                            <a
                                className="min-h-11 content-center px-2 text-sm font-medium text-foreground transition-colors hover:text-primary focus-visible:rounded-sm focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-ring"
                                href="/login"
                            >
                                Log in
                            </a>
                        ))}
                    <Button
                        nativeButton={false}
                        render={<Link href={publicPageUrl("/rooms")} />}
                        className="h-11 rounded-lg px-5 font-semibold"
                        variant="default"
                    >
                        <Calendar aria-hidden="true" />
                        Book now
                    </Button>
                </div>
                <Button
                    aria-controls="public-mobile-navigation"
                    aria-expanded={isMenuOpen}
                    aria-label={
                        isMenuOpen
                            ? "Close navigation menu"
                            : "Open navigation menu"
                    }
                    className="size-11 lg:hidden"
                    onClick={() => setIsMenuOpen((open) => !open)}
                    size="icon"
                    variant="outline"
                >
                    {isMenuOpen ? (
                        <X aria-hidden="true" className="size-5" />
                    ) : (
                        <Menu aria-hidden="true" className="size-5" />
                    )}
                </Button>
            </nav>

            <AnimatePresence initial={false}>
                {isMenuOpen ? (
                    <motion.div
                        animate={{ height: "auto", opacity: 1 }}
                        className="overflow-hidden border-t border-border bg-background lg:hidden"
                        exit={{ height: 0, opacity: 0 }}
                        id="public-mobile-navigation"
                        initial={
                            reduceMotion ? false : { height: 0, opacity: 0 }
                        }
                        transition={{
                            duration: reduceMotion ? 0 : 0.2,
                            ease: "easeOut",
                        }}
                    >
                        <div className="container mx-auto max-w-7xl px-4 py-4 sm:px-6">
                            <div className="flex flex-col">
                                {navLinks.map((item) => {
                                    const isActive =
                                        item.href === "/"
                                            ? pathname === "/"
                                            : pathname.startsWith(item.href);
                                    return (
                                        <Link
                                            key={item.name}
                                            onClick={closeMenu}
                                            href={publicPageUrl(item.href)}
                                            className={cn(
                                                "flex min-h-12 items-center justify-between border-b border-border px-1 text-base font-medium transition-colors hover:text-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring",
                                                isActive
                                                    ? "text-primary"
                                                    : "text-foreground",
                                            )}
                                        >
                                            {item.name}
                                            <ArrowUpRight
                                                aria-hidden="true"
                                                className="size-4 text-muted-foreground"
                                            />
                                        </Link>
                                    );
                                })}
                            </div>
                            {!isLoading &&
                                (isAuthenticated ? (
                                    <Button
                                        nativeButton={false}
                                        render={
                                            <Link
                                                href="/dashboard"
                                                onClick={closeMenu}
                                            />
                                        }
                                        className="mt-4 h-11 w-full font-semibold"
                                        variant="default"
                                    >
                                        <LayoutDashboard
                                            aria-hidden="true"
                                            className="size-4"
                                        />
                                        Dashboard
                                    </Button>
                                ) : (
                                    <div className="grid grid-cols-2 gap-3 pt-4">
                                        <Button
                                            nativeButton={false}
                                            render={
                                                <a
                                                    href="/login"
                                                    onClick={closeMenu}
                                                />
                                            }
                                            className="h-11 w-full rounded-lg font-semibold"
                                            variant="outline"
                                        >
                                            Log in
                                        </Button>
                                        <Button
                                            nativeButton={false}
                                            render={
                                                <a
                                                    href="/register"
                                                    onClick={closeMenu}
                                                />
                                            }
                                            className="h-11 w-full rounded-lg font-semibold"
                                            variant="outline"
                                        >
                                            Register
                                        </Button>
                                        <Button
                                            nativeButton={false}
                                            render={
                                                <Link
                                                    href={publicPageUrl(
                                                        "/rooms",
                                                    )}
                                                    onClick={closeMenu}
                                                />
                                            }
                                            className="col-span-2 h-11 w-full rounded-lg font-semibold"
                                            variant="default"
                                        >
                                            <Calendar
                                                aria-hidden="true"
                                                className="size-4"
                                            />
                                            Book now
                                        </Button>
                                    </div>
                                ))}
                        </div>
                    </motion.div>
                ) : null}
            </AnimatePresence>
        </header>
    );
}

function Footer() {
    const currentYear = new Date().getFullYear();
    const scrollToTop = () => {
        if (typeof window !== "undefined") {
            window.scrollTo({ top: 0, behavior: "smooth" });
        }
    };

    const exploreLinks = [
        { name: "Home", href: "/" },
        { name: "Rooms & Suites", href: "/rooms" },
        { name: "About Nivara", href: "/about" },
        { name: "Amenities", href: "/#amenities" },
    ];

    const guestServiceLinks = [
        { name: "Book a Stay", href: "/checkout", external: false },
        { name: "Contact Concierge", href: "/contact", external: false },
        { name: "Guest Sign In", href: "/login", external: true },
        { name: "Create Account", href: "/register", external: true },
    ];

    const linkClass =
        "inline-flex items-center gap-1.5 text-sm text-[#F5EDE4]/75 transition-colors duration-200 hover:text-[#D7B995] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#D7B995]";

    return (
        <footer className="w-full overflow-x-clip border-t border-[#D7B995]/20 bg-[#282522] pt-12 pb-7 font-sans text-[#F5EDE4]">
            <div className="mx-auto max-w-7xl px-6 sm:px-8 lg:px-12">
                <div className="grid grid-cols-1 gap-10 pb-10 md:grid-cols-2 lg:grid-cols-[1.35fr_0.85fr_0.85fr_1.15fr] lg:gap-10">
                    {/* Column 1: Brand, Description, Socials & Explore CTA */}
                    <div className="flex min-w-0 flex-col items-start space-y-4">
                        <Logo inverse />
                        <p className="max-w-xs text-sm leading-relaxed text-[#F5EDE4]/75">
                            A serene boutique retreat in{" "}
                            {HOTEL_DETAILS.location}, blending warm hospitality
                            with refined comfort for business and leisure stays.
                        </p>

                        <div className="flex items-center gap-2.5 pt-1">
                            <a
                                href="#instagram"
                                aria-label="Nivara on Instagram"
                                className="inline-flex size-8 items-center justify-center rounded-full border border-[#F5EDE4]/15 text-[#F5EDE4]/75 transition-colors duration-200 hover:border-[#D7B995] hover:text-[#D7B995]"
                            >
                                <svg
                                    aria-hidden="true"
                                    className="size-4"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth="1.75"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                >
                                    <rect width="20" height="20" x="2" y="2" rx="5" ry="5" />
                                    <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z" />
                                    <line x1="17.5" x2="17.51" y1="6.5" y2="6.5" />
                                </svg>
                            </a>
                            <a
                                href="#facebook"
                                aria-label="Nivara on Facebook"
                                className="inline-flex size-8 items-center justify-center rounded-full border border-[#F5EDE4]/15 text-[#F5EDE4]/75 transition-colors duration-200 hover:border-[#D7B995] hover:text-[#D7B995]"
                            >
                                <svg
                                    aria-hidden="true"
                                    className="size-4"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth="1.75"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                >
                                    <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z" />
                                </svg>
                            </a>
                        </div>

                        <Link
                            href={publicPageUrl("/rooms")}
                            onClick={scrollToTop}
                            className="inline-flex items-center gap-1.5 pt-1 text-xs font-semibold tracking-[0.16em] text-[#D7B995] uppercase transition-colors duration-200 hover:text-[#F5EDE4]"
                        >
                            <span>Explore Rooms</span>
                            <ArrowUpRight
                                aria-hidden="true"
                                className="size-3.5"
                            />
                        </Link>
                    </div>

                    {/* Column 2: Explore */}
                    <div className="flex min-w-0 flex-col">
                        <h4 className="mb-4 text-xs font-semibold tracking-[0.2em] dark:text-primary text-white  uppercase">
                            Explore
                        </h4>
                        <ul className="space-y-3">
                            {exploreLinks.map((link) => (
                                <li key={link.href}>
                                    <Link
                                        href={publicPageUrl(link.href)}
                                        onClick={scrollToTop}
                                        className={linkClass}
                                    >
                                        {link.name}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </div>

                    {/* Column 3: Guest Services */}
                    <div className="flex min-w-0 flex-col">
                        <h4 className="mb-4 text-xs font-semibold tracking-[0.2em] dark:text-primary text-white  uppercase">
                            Guest Services
                        </h4>
                        <ul className="space-y-3">
                            {guestServiceLinks.map((link) => (
                                <li key={link.href}>
                                    {link.external ? (
                                        <a
                                            href={link.href}
                                            className={linkClass}
                                        >
                                            {link.name}
                                        </a>
                                    ) : (
                                        <Link
                                            href={publicPageUrl(link.href)}
                                            onClick={scrollToTop}
                                            className={linkClass}
                                        >
                                            {link.name}
                                        </Link>
                                    )}
                                </li>
                            ))}
                        </ul>
                    </div>

                    {/* Column 4: Contact & Reservations */}
                    <div className="flex min-w-0 flex-col">
                        <h4 className="mb-4 text-xs font-semibold tracking-[0.2em] dark:text-primary text-white  uppercase">
                            Contact
                        </h4>
                        <ul className="space-y-3 text-sm text-[#F5EDE4]/75">
                            <li className="flex items-start gap-2.5">
                                <MapPin
                                    aria-hidden="true"
                                    className="mt-0.5 size-4 shrink-0 text-[#D7B995]"
                                />
                                <span className="leading-relaxed">
                                    {HOTEL_DETAILS.address ??
                                        HOTEL_DETAILS.location}
                                </span>
                            </li>

                            {HOTEL_DETAILS.phone && (
                                <li className="flex items-center gap-2.5">
                                    <Phone
                                        aria-hidden="true"
                                        className="size-4 shrink-0 text-[#D7B995]"
                                    />
                                    <a
                                        href={`tel:${HOTEL_DETAILS.phone}`}
                                        className="transition-colors duration-200 hover:text-[#D7B995]"
                                    >
                                        {HOTEL_DETAILS.phone}
                                    </a>
                                </li>
                            )}

                            {HOTEL_DETAILS.email && (
                                <li className="flex items-center gap-2.5">
                                    <Mail
                                        aria-hidden="true"
                                        className="size-4 shrink-0 text-[#D7B995]"
                                    />
                                    <a
                                        href={`mailto:${HOTEL_DETAILS.email}`}
                                        className="break-all transition-colors duration-200 hover:text-[#D7B995]"
                                    >
                                        {HOTEL_DETAILS.email}
                                    </a>
                                </li>
                            )}
                        </ul>

                    </div>
                </div>

                {/* Divider */}
                <Separator className="h-px w-full bg-[#F5EDE4]/12" />

                {/* Bottom Copyright & Tagline */}
                <div className="flex flex-col items-center justify-between gap-2 pt-6 text-xs text-[#F5EDE4]/60 sm:flex-row">
                    <span>
                        &copy; {currentYear} {HOTEL_DETAILS.name} Hotel &amp;
                        Resort. All rights reserved.
                    </span>
                    <span className="text-[#D7B995]/80">
                        Thoughtful hospitality in {HOTEL_DETAILS.location}
                    </span>
                </div>
            </div>
        </footer>
    );
}

export function PublicLayout({
    children,
}: { children?: React.ReactNode } = {}) {
    const { url } = usePage();

    useEffect(() => {
        if (typeof window !== "undefined") {
            const searchParams = new URLSearchParams(window.location.search);
            const preferences = getBookingPreferences(searchParams);
            saveBookingPreferences(preferences);
        }
    }, [url]);

    return (
        <>
            <Navbar />
            <main className="min-w-0 flex-1" id="main-content" tabIndex={-1}>
                {/* <PageTransition>{children}</PageTransition> */}
                {children}
            </main>
            <Footer />
        </>
    );
}

export default PublicLayout;
