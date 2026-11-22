import { usePage, Link } from "@inertiajs/react";
import type { SharedProps } from "@/types";

import { DropdownProfile } from "@/components/common/DropdownProfile";
import { PageTransition } from "@/components/common/PageTransition";
import { AppSidebar } from "@/components/sidebar/AppSidebar";
import {
  SidebarInset,
  SidebarProvider,
  SidebarTrigger,
} from "@/components/ui/sidebar";
import { NotificationsPopover } from "@/features/notifications/components/NotificationsPopover";

export function DashboardLayout({
  children,
}: {
  children?: React.ReactNode;
} = {}) {
  const { auth } = usePage<SharedProps>().props;

  return (
    <SidebarProvider>
      <AppSidebar />
      <SidebarInset>
        <header className="flex h-16 shrink-0 items-center justify-between gap-2 border-b border-border/70 px-4">
          <div className="flex items-center gap-2">
            <SidebarTrigger className="-ml-1" />
          </div>
          <div className="flex items-center gap-3">
            <NotificationsPopover />
            <DropdownProfile />
          </div>
        </header>

        <main className="flex-1 p-6">
          <PageTransition>{children}</PageTransition>
        </main>
      </SidebarInset>
    </SidebarProvider>
  );
}

export default DashboardLayout;
