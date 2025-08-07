import { QueryClient, QueryFunction } from "@tanstack/react-query";

async function throwIfResNotOk(res: Response) {
  if (!res.ok) {
    let errorMessage: string;
    try {
      const text = await res.text();
      // Try to parse as JSON to get the structured error message
      try {
        const jsonError = JSON.parse(text);
        errorMessage = jsonError.message || jsonError.error || text || res.statusText;
      } catch {
        errorMessage = text || res.statusText;
      }
    } catch {
      errorMessage = res.statusText;
    }
    
    const error = new Error(`${res.status}: ${errorMessage}`);
    // Add status code to error for better handling
    (error as any).status = res.status;
    throw error;
  }
}

export async function apiRequest(
  method: string,
  url: string,
  data?: unknown | undefined,
): Promise<Response> {
  const res = await fetch(url, {
    method,
    headers: data ? { "Content-Type": "application/json" } : {},
    body: data ? JSON.stringify(data) : undefined,
    credentials: "include",
  });

  await throwIfResNotOk(res);
  return res;
}

type UnauthorizedBehavior = "returnNull" | "throw";
export const getQueryFn: <T>(options: {
  on401: UnauthorizedBehavior;
}) => QueryFunction<T> =
  ({ on401: unauthorizedBehavior }) =>
  async ({ queryKey }) => {
    const res = await fetch(queryKey.join("/") as string, {
      credentials: "include",
    });

    if (unauthorizedBehavior === "returnNull" && res.status === 401) {
      return null;
    }

    await throwIfResNotOk(res);
    return await res.json();
  };

export const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      queryFn: getQueryFn({ on401: "returnNull" }),
      refetchInterval: false,
      refetchOnWindowFocus: false,
      staleTime: 5 * 60 * 1000,
      retry: (failureCount, error: any) => {
        // Don't retry on authentication errors
        if (error?.status === 401 || error?.status === 403) {
          return false;
        }
        // Don't retry on client errors (4xx)
        if (error?.status >= 400 && error?.status < 500) {
          return false;
        }
        // Only retry server errors (5xx) up to 2 times
        return failureCount < 2;
      },
    },
    mutations: {
      retry: false,
      onError: (error: any) => {
        // Handle specific error types
        console.warn('Mutation error:', error?.message || 'Unknown error');
        
        // Don't throw for authentication errors - let components handle them
        if (error?.status === 401 || error?.status === 403) {
          return;
        }
      },
    },
  },
});
