import * as React from 'react';
import { cva, type VariantProps } from 'class-variance-authority';
import { cn } from '@/lib/utils';

const badgeVariants = cva(
    'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2',
    {
        variants: {
            variant: {
                default:
                    'border-transparent bg-primary text-primary-foreground shadow hover:bg-primary/80',
                secondary:
                    'border-transparent bg-secondary text-secondary-foreground hover:bg-secondary/80',
                destructive:
                    'border-transparent bg-destructive text-destructive-foreground shadow hover:bg-destructive/80',
                outline:
                    'text-foreground border-primary/30 hover:bg-primary/5',
                success:
                    'border-transparent bg-green-500/10 text-green-600 dark:text-green-400',
                warning:
                    'border-transparent bg-yellow-500/10 text-yellow-600 dark:text-yellow-400',
                info:
                    'border-transparent bg-blue-500/10 text-blue-600 dark:text-blue-400',
                gradient:
                    'border-transparent bg-gradient-to-r from-primary to-accent text-white shadow-lg shadow-primary/25',
                glass:
                    'border-white/20 bg-white/10 backdrop-blur-sm text-foreground',
                pulse:
                    'border-transparent bg-primary text-primary-foreground animate-pulse',
                glow:
                    'border-transparent bg-primary text-primary-foreground shadow-lg shadow-primary/50 animate-glow',
            },
            size: {
                default: 'px-2.5 py-0.5 text-xs',
                sm: 'px-2 py-0.5 text-[10px]',
                lg: 'px-3 py-1 text-sm',
            },
        },
        defaultVariants: {
            variant: 'default',
            size: 'default',
        },
    }
);

export interface BadgeProps
    extends React.HTMLAttributes<HTMLDivElement>,
        VariantProps<typeof badgeVariants> {
    icon?: React.ReactNode;
    removable?: boolean;
    onRemove?: () => void;
}

function Badge({
    className,
    variant,
    size,
    icon,
    removable,
    onRemove,
    children,
    ...props
}: BadgeProps) {
    return (
        <div className={cn(badgeVariants({ variant, size }), className)} {...props}>
            {icon && <span className="mr-1">{icon}</span>}
            {children}
            {removable && (
                <button
                    onClick={(e) => {
                        e.stopPropagation();
                        onRemove?.();
                    }}
                    className="ml-1 rounded-full hover:bg-white/20 p-0.5 transition-colors"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="12"
                        height="12"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        strokeWidth="2"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                    >
                        <path d="M18 6 6 18" />
                        <path d="m6 6 12 12" />
                    </svg>
                </button>
            )}
        </div>
    );
}

// Animated Badge with dot indicator
interface AnimatedBadgeProps extends BadgeProps {
    animated?: boolean;
    dotColor?: string;
}

function AnimatedBadge({
    animated = true,
    dotColor = 'bg-green-500',
    children,
    className,
    ...props
}: AnimatedBadgeProps) {
    return (
        <Badge className={cn('relative', className)} {...props}>
            {animated && (
                <span className="absolute -top-0.5 -right-0.5 flex h-2 w-2">
                    <span className={cn('animate-ping absolute inline-flex h-full w-full rounded-full opacity-75', dotColor)} />
                    <span className={cn('relative inline-flex rounded-full h-2 w-2', dotColor)} />
                </span>
            )}
            {children}
        </Badge>
    );
}

// Status Badge with predefined states
type StatusType = 'online' | 'offline' | 'busy' | 'away' | 'pending';

interface StatusBadgeProps extends Omit<BadgeProps, 'variant'> {
    status: StatusType;
}

const statusConfig: Record<StatusType, { variant: BadgeProps['variant']; label: string; dotColor: string }> = {
    online: { variant: 'success', label: 'Online', dotColor: 'bg-green-500' },
    offline: { variant: 'secondary', label: 'Offline', dotColor: 'bg-muted-foreground' },
    busy: { variant: 'destructive', label: 'Busy', dotColor: 'bg-red-500' },
    away: { variant: 'warning', label: 'Away', dotColor: 'bg-yellow-500' },
    pending: { variant: 'info', label: 'Pending', dotColor: 'bg-blue-500' },
};

function StatusBadge({ status, children, className, ...props }: StatusBadgeProps) {
    const config = statusConfig[status];

    return (
        <Badge
            variant={config.variant}
            className={cn('gap-1.5', className)}
            {...props}
        >
            <span className={cn('w-1.5 h-1.5 rounded-full', config.dotColor)} />
            {children || config.label}
        </Badge>
    );
}

// Counter Badge
interface CounterBadgeProps extends BadgeProps {
    count: number;
    max?: number;
}

function CounterBadge({ count, max = 99, className, ...props }: CounterBadgeProps) {
    const displayCount = count > max ? `${max}+` : count;

    return (
        <Badge
            className={cn('min-w-[1.25rem] justify-center', className)}
            {...props}
        >
            {displayCount}
        </Badge>
    );
}

export { Badge, AnimatedBadge, StatusBadge, CounterBadge, badgeVariants };
