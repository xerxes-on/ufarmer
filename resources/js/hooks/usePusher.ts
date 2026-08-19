import { useEffect, useRef, useState } from 'react';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

(window as any).Pusher = Pusher;

export interface CalendarProgress {
    calendar_run_id: number;
    progress: number;
    status: 'pending' | 'processing' | 'completed' | 'failed';
    is_loading: boolean;
    message: string | null;
}

let echoInstance: Echo<'pusher'> | null = null;
let echoToken: string | null = null;

function getEcho(token: string): Echo<'pusher'> {
    if (echoInstance && echoToken === token) return echoInstance;

    if (echoInstance) {
        echoInstance.disconnect();
        echoInstance = null;
        echoToken = null;
    }

    echoToken = token;
    echoInstance = new Echo({
        broadcaster: 'pusher',
        key: '89e486a5b34fe3e53fb6',
        cluster: 'eu',
        forceTLS: true,
        authEndpoint: '/api/broadcasting/auth',
        auth: {
            headers: {
                Authorization: `Bearer ${token}`,
                'x-application-alias': 'farmer',
            },
        },
    });

    return echoInstance;
}

export function useCalendarProgress(authId: number | null, token: string): CalendarProgress | null {
    const [progress, setProgress] = useState<CalendarProgress | null>(null);
    const timeoutRef = useRef<number | null>(null);

    useEffect(() => {
        if (!authId || !token) return;

        const echo = getEcho(token);
        const channelName = `calendar-progress.${authId}`;

        const channel = echo.private(channelName);
        channel.listen('.calendar.run.progress', (data: CalendarProgress) => {
            setProgress(data);

            if (timeoutRef.current) {
                window.clearTimeout(timeoutRef.current);
                timeoutRef.current = null;
            }

            if (data.status === 'completed' || data.status === 'failed') {
                timeoutRef.current = window.setTimeout(() => setProgress(null), 3000);
            }
        });

        return () => {
            if (timeoutRef.current) {
                window.clearTimeout(timeoutRef.current);
                timeoutRef.current = null;
            }
            echo.leave(channelName);
        };
    }, [authId, token]);

    return progress;
}

export function disconnectEcho() {
    if (echoInstance) {
        echoInstance.disconnect();
        echoInstance = null;
        echoToken = null;
    }
}
