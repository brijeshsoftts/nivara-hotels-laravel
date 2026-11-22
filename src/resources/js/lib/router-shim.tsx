import React from "react";
import { Link as InertiaLink, router, usePage } from "@inertiajs/react";

export interface LinkProps extends React.AnchorHTMLAttributes<HTMLAnchorElement> {
  to?: string;
  href?: string;
  replace?: boolean;
}

export const Link = React.forwardRef<HTMLAnchorElement, LinkProps>(
  ({ to, href, className, children, ...props }, ref) => {
    const destination = to || href || "#";

    if (
      typeof destination === "string" &&
      (destination.startsWith("http://") ||
        destination.startsWith("https://") ||
        destination.startsWith("mailto:") ||
        destination.startsWith("tel:") ||
        destination.startsWith("#"))
    ) {
      return (
        <a ref={ref} href={destination} className={className} {...props}>
          {children}
        </a>
      );
    }

    return (
      <InertiaLink ref={ref} href={destination} className={className} {...props}>
        {children}
      </InertiaLink>
    );
  }
);
Link.displayName = "Link";

export const NavLink = React.forwardRef<
  HTMLAnchorElement,
  LinkProps & {
    className?: string | ((props: { isActive: boolean }) => string);
  }
>(({ to, href, className, children, ...props }, ref) => {
  const destination = to || href || "#";
  const pathname = typeof window !== "undefined" ? window.location.pathname : "";
  const isActive = pathname === destination;
  const computedClass =
    typeof className === "function" ? className({ isActive }) : className;

  return (
    <InertiaLink ref={ref} href={destination} className={computedClass} {...props}>
      {children}
    </InertiaLink>
  );
});
NavLink.displayName = "NavLink";

export const useNavigate = () => {
  return React.useCallback((to: any, options?: any) => {
    if (typeof to === "number") {
      window.history.go(to);
    } else if (typeof to === "string") {
      router.visit(to, options);
    } else if (to && typeof to === "object" && to.pathname) {
      const url = `${to.pathname}${to.search || ""}${to.hash || ""}`;
      router.visit(url, options);
    }
  }, []);
};

export const useLocation = () => {
  const page = usePage();
  return {
    pathname: typeof window !== "undefined" ? window.location.pathname : "/",
    search: typeof window !== "undefined" ? window.location.search : "",
    hash: typeof window !== "undefined" ? window.location.hash : "",
    state: null,
    key: page.component || "default",
  };
};

export const useParams = <T extends Record<string, string | undefined>>(): T => {
  const page = usePage();
  const propsParams = (page.props as any)?.params || {};

  if (typeof window === "undefined") {
    return propsParams as T;
  }

  const pathParts = window.location.pathname.split("/").filter(Boolean);
  const derivedParams: Record<string, string> = { ...propsParams };

  if (pathParts[0] === "rooms" && pathParts[1] && pathParts[1] !== "book") {
    derivedParams["slug"] = pathParts[1];
  }

  return derivedParams as T;
};

export const useSearchParams = () => {
  const [searchParams, setSearchParamsState] = React.useState(
    () => new URLSearchParams(typeof window !== "undefined" ? window.location.search : "")
  );

  const setSearchParams = React.useCallback(
    (nextInit: any) => {
      const nextParams = typeof nextInit === "function" ? nextInit(searchParams) : nextInit;
      const sp = new URLSearchParams(nextParams);
      setSearchParamsState(sp);
      if (typeof window !== "undefined") {
        const searchStr = sp.toString();
        const url = `${window.location.pathname}${searchStr ? `?${searchStr}` : ""}`;
        router.visit(url, {
          preserveState: true,
          preserveScroll: true,
          replace: true,
        });
      }
    },
    [searchParams]
  );

  return [searchParams, setSearchParams] as const;
};

export const Navigate = ({ to, replace }: { to: string; replace?: boolean }) => {
  React.useEffect(() => {
    if (typeof window !== "undefined") {
      router.visit(to, { replace });
    }
  }, [to, replace]);

  return null;
};

export const useOutlet = () => null;
export const useMatches = () => [];
export const Outlet = ({ children }: any) => <>{children}</>;
export const createBrowserRouter = () => ({});
export const RouterProvider = ({ children }: any) => <>{children}</>;
