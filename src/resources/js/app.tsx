import "../css/app.css";
import { createInertiaApp, usePage } from "@inertiajs/react";
import { createRoot } from "react-dom/client";
import { useEffect } from "react";
import { ThemeProvider } from "@/components/theme-provider";
import { Toaster, toast } from "sonner";

interface PageProps {
  flash?: {
    success?: string | null;
    error?: string | null;
    message?: string | null;
  };
  [key: string]: unknown;
}

function FlashMessages() {
  const { props } = usePage<PageProps>();
  const flash = props.flash;

  useEffect(() => {
    if (flash?.success) {
      toast.success(flash.success);
    }
    if (flash?.error) {
      toast.error(flash.error);
    }
    if (flash?.message && !flash.success && !flash.error) {
      toast.info(flash.message);
    }
  }, [flash]);

  return null;
}

createInertiaApp({
  title: (title) => (title ? `${title} — Nivara Hotels` : "Nivara Hotels"),
  resolve: (name) => {
    const pages = import.meta.glob("./Pages/**/*.tsx", { eager: true });
    let page = pages[`./Pages/${name}.tsx`] as any;
    if (!page) {
      page = pages[`./Pages/${name}/index.tsx`] as any;
    }
    if (!page) {
      throw new Error(`Inertia page not found: ./Pages/${name}.tsx`);
    }
    return page;
  },
  setup({ el, App, props }) {
    createRoot(el).render(
      <ThemeProvider defaultTheme="light">
        <FlashMessages />
        <App {...props} />
        <Toaster position="top-right" richColors />
      </ThemeProvider>
    );
  },
});
