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
        const pages = import.meta.glob("./pages/**/*.tsx", { eager: true });
        let page = pages[`./pages/${name}.tsx`] as any;
        if (!page) {
            page = pages[`./pages/${name}/index.tsx`] as any;
        }
        if (!page) {
            const lowerName = name.toLowerCase();
            for (const path in pages) {
                const cleanPath = path
                    .replace(/^\.\/pages\//, "")
                    .replace(/\.tsx$/, "");
                if (
                    cleanPath.toLowerCase() === lowerName ||
                    cleanPath.toLowerCase() === `${lowerName}/index`
                ) {
                    page = pages[path];
                    break;
                }
            }
        }
        if (!page) {
            throw new Error(`Inertia page not found: ./pages/${name}.tsx`);
        }
        return page;
    },
    setup({ el, App, props }) {
        createRoot(el).render(
            <ThemeProvider defaultTheme="light">
                {/* <FlashMessages /> */}
                <App {...props} />
                <Toaster position="top-right" richColors />
            </ThemeProvider>,
        );
    },
});
