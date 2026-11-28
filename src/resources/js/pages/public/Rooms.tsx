import { cn } from "cn";
import {
    AirVent,
    Briefcase,
    ConciergeBell,
    Droplet,
    Refrigerator,
    Tv,
    Wifi,
} from "lucide-react";
import { usePage } from "@inertiajs/react";

import HeroImage from "@/assets/landing_hero.png";
import {
    StaggerContainer,
    StaggerItem,
} from "@/components/common/PageTransition";
import { Button } from "@/components/ui/button";
import { BookingBar } from "@/features/public/components/BookingBar";
import { HoverText } from "@/features/public/components/hoverText";
import { RoomCard, RoomsSkeleton } from "@/features/public/components/RoomCard";
import {
    sectionEyebrowClassName,
    sectionHeadingClassName,
} from "@/features/public/components/sectionStyles";
import { PublicLayout } from "@/layouts/PublicLayout";

import { sectionPaddingClassName } from "@/features/public/components/LandingPage";

function HeroSection() {
    return (
        <section className="relative w-full bg-background font-sans">
            <div className="absolute inset-0 z-0 w-full overflow-hidden">
                <img
                    aria-hidden="true"
                    className="absolute inset-0 h-full w-full object-cover object-center"
                    fetchPriority="high"
                    src={HeroImage}
                />
                <div className="absolute inset-0 bg-linear-to-r from-black/60 via-black/30 to-black/10" />
            </div>

            <div className="relative z-10 container mx-auto flex flex-col justify-center px-6 pt-16 pb-4 md:px-12 md:pt-3 md:pb-0 lg:p-16">
                <div>
                    <span
                        className={cn(sectionEyebrowClassName, "text-white/70")}
                    >
                        Our Rooms
                    </span>

                    <h1
                        className={cn(
                            sectionHeadingClassName,
                            "mb-6 text-4xl leading-[1.05] text-white sm:text-5xl md:text-5xl lg:text-[72px]",
                        )}
                    >
                        <HoverText
                            text="Find Your Perfect"
                            staggerDelay={30}
                            duration={400}
                        />{" "}
                        <br />
                        <HoverText
                            text="Stay Experience"
                            staggerDelay={30}
                            duration={400}
                        />
                    </h1>

                    <p className="mb-4 max-w-150 text-base leading-relaxed font-light text-white/80 md:mb-2 lg:mb-6 lg:text-lg">
                        From cozy rooms to luxurious rooms, each space is
                        designed with comfort, elegance and breathtaking views.
                    </p>
                </div>
            </div>

            <div className="relative z-30 top-14 md:top-10">
                <BookingBar />
            </div>
        </section>
    );
}

function RoomsSection({ rooms: propRooms }: { rooms?: any[] } = {}) {
    const page = usePage<any>();
    const rooms = propRooms ?? (page.props?.rooms?.data as any[]) ?? [];

    return (
        <section
            className={cn("w-full bg-muted font-sans", sectionPaddingClassName)}
        >
            <div className="container mx-auto px-4 md:px-8 lg:px-12">
                <div className="flex flex-col items-start justify-between md:flex-row md:items-center">
                    <div>
                        <span className={sectionEyebrowClassName}>
                            Our Rooms
                        </span>

                        <h2 className={sectionHeadingClassName}>
                            <HoverText staggerDelay={30} duration={400}>
                                Handpicked Rooms for a{" "}
                                <span className="pr-2 text-primary italic">
                                    Memorable Stay
                                </span>
                            </HoverText>
                        </h2>
                    </div>
                </div>

                <StaggerContainer className="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {rooms.length === 0 ? (
                        <p className="col-span-full py-12 text-center text-sm text-muted-foreground">
                            No rooms match these filters.
                        </p>
                    ) : (
                        rooms.map((room) => (
                            <StaggerItem key={room.id}>
                                <RoomCard room={room} />
                            </StaggerItem>
                        ))
                    )}
                </StaggerContainer>
            </div>
        </section>
    );
}

function IncludedAmenitiesBar() {
    const includedAmenities = [
        { label: "Air Conditioning", icon: AirVent },
        { label: "Free Wi-Fi", icon: Wifi },
        { label: "Minibar", icon: Refrigerator },
        { label: "24/7 Room Service", icon: ConciergeBell },
        { label: "LED TV", icon: Tv },
        { label: "Premium Toiletries", icon: Droplet },
        { label: "Daily Housekeeping", icon: Briefcase },
    ];
    return (
        <section className="flex w-full justify-center bg-background px-4 py-8 font-sans md:px-8 md:py-12">
            <div className="flex w-full max-w-300 flex-col items-center gap-8 rounded-2xl border border-border/60 bg-secondary/40 p-8 shadow-sm md:rounded-2xl md:p-10 lg:flex-row lg:gap-12">
                <div className="shrink-0 text-center lg:text-left">
                    <h3 className="font-heading text-2xl leading-tight tracking-tight text-foreground md:text-[28px]">
                        <HoverText staggerDelay={30} duration={400}>
                            All Rooms Include <br className="hidden lg:block" />
                            These Amenities
                        </HoverText>
                    </h3>
                </div>

                <div className="h-px w-full shrink-0 bg-primary/20 lg:h-16 lg:w-px" />

                <div className="flex w-full flex-wrap items-start justify-center gap-6 md:gap-4 lg:flex-nowrap lg:justify-between">
                    {includedAmenities.map((item, index) => {
                        const Icon = item.icon;
                        return (
                            <div
                                key={index}
                                className="group flex w-25 flex-col items-center gap-3 lg:w-auto"
                            >
                                <div className="text-primary transition-transform duration-300 group-hover:-translate-y-1">
                                    <Icon className="h-7 w-7 stroke-[1.25] md:h-7.5 md:w-7.5" />
                                </div>

                                <span className="text-center text-2xs leading-tight font-medium text-foreground/90 md:text-xs">
                                    {item.label}
                                </span>
                            </div>
                        );
                    })}
                </div>
            </div>
        </section>
    );
}

export default function RoomsPage({ rooms }: { rooms?: any } = {}) {
    return (
        <PublicLayout>
            <HeroSection />
            <RoomsSection rooms={rooms?.data} />
            <IncludedAmenitiesBar />
        </PublicLayout>
    );
}
