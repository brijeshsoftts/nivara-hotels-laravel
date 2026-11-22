import "../css/app.css";
import { createInertiaApp } from "@inertiajs/react";
import { createRoot } from "react-dom/client";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { ThemeProvider } from "@/components/theme-provider";
import { AuthProvider } from "@/providers/AuthProvider";
import { Toaster } from "@/components/ui/sonner";

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      retry: false,
      staleTime: 1000 * 60 * 5,
    },
  },
});

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
      <QueryClientProvider client={queryClient}>
        <ThemeProvider defaultTheme="light">
          <AuthProvider>
            <App {...props} />
            <Toaster position="top-right" />
          </AuthProvider>
        </ThemeProvider>
      </QueryClientProvider>
    );
  },
});
