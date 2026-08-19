import * as React from 'react';
import { cn } from '@/lib/utils';

interface Card3DProps extends React.HTMLAttributes<HTMLDivElement> {
    tiltEnabled?: boolean;
    maxTilt?: number;
    glareEnabled?: boolean;
    glareMaxOpacity?: number;
}

const Card = React.forwardRef<HTMLDivElement, Card3DProps>(
    ({ className, tiltEnabled = false, maxTilt = 10, glareEnabled = false, glareMaxOpacity = 0.3, children, ...props }, ref) => {
        const cardRef = React.useRef<HTMLDivElement>(null);
        const [transform, setTransform] = React.useState({ rotateX: 0, rotateY: 0 });
        const [glarePosition, setGlarePosition] = React.useState({ x: 50, y: 50 });

        React.useImperativeHandle(ref, () => cardRef.current!);

        const handleMouseMove = React.useCallback(
            (e: React.MouseEvent<HTMLDivElement>) => {
                if (!tiltEnabled || !cardRef.current) return;

                const rect = cardRef.current.getBoundingClientRect();
                const centerX = rect.left + rect.width / 2;
                const centerY = rect.top + rect.height / 2;
                const mouseX = e.clientX - centerX;
                const mouseY = e.clientY - centerY;

                const rotateY = (mouseX / (rect.width / 2)) * maxTilt;
                const rotateX = -(mouseY / (rect.height / 2)) * maxTilt;

                setTransform({ rotateX, rotateY });

                if (glareEnabled) {
                    const glareX = ((e.clientX - rect.left) / rect.width) * 100;
                    const glareY = ((e.clientY - rect.top) / rect.height) * 100;
                    setGlarePosition({ x: glareX, y: glareY });
                }
            },
            [tiltEnabled, maxTilt, glareEnabled]
        );

        const handleMouseLeave = React.useCallback(() => {
            if (!tiltEnabled) return;
            setTransform({ rotateX: 0, rotateY: 0 });
            setGlarePosition({ x: 50, y: 50 });
        }, [tiltEnabled]);

        return (
            <div
                ref={cardRef}
                className={cn(
                    'rounded-2xl border bg-card text-card-foreground shadow-lg transition-all duration-300',
                    tiltEnabled && 'cursor-pointer',
                    className
                )}
                style={{
                    transform: tiltEnabled
                        ? `perspective(1000px) rotateX(${transform.rotateX}deg) rotateY(${transform.rotateY}deg)`
                        : undefined,
                    transformStyle: 'preserve-3d',
                }}
                onMouseMove={handleMouseMove}
                onMouseLeave={handleMouseLeave}
                {...props}
            >
                {glareEnabled && (
                    <div
                        className="pointer-events-none absolute inset-0 rounded-2xl overflow-hidden"
                        style={{
                            background: `radial-gradient(circle at ${glarePosition.x}% ${glarePosition.y}%, rgba(255,255,255,${glareMaxOpacity}), transparent 60%)`,
                            opacity: transform.rotateX !== 0 || transform.rotateY !== 0 ? 1 : 0,
                            transition: 'opacity 0.3s ease',
                        }}
                    />
                )}
                {children}
            </div>
        );
    }
);
Card.displayName = 'Card';

const CardHeader = React.forwardRef<HTMLDivElement, React.HTMLAttributes<HTMLDivElement>>(
    ({ className, ...props }, ref) => (
        <div
            ref={ref}
            className={cn('flex flex-col space-y-1.5 p-6', className)}
            {...props}
        />
    )
);
CardHeader.displayName = 'CardHeader';

const CardTitle = React.forwardRef<HTMLParagraphElement, React.HTMLAttributes<HTMLHeadingElement>>(
    ({ className, ...props }, ref) => (
        <h3
            ref={ref}
            className={cn('text-2xl font-semibold leading-none tracking-tight', className)}
            {...props}
        />
    )
);
CardTitle.displayName = 'CardTitle';

const CardDescription = React.forwardRef<HTMLParagraphElement, React.HTMLAttributes<HTMLParagraphElement>>(
    ({ className, ...props }, ref) => (
        <p
            ref={ref}
            className={cn('text-sm text-muted-foreground', className)}
            {...props}
        />
    )
);
CardDescription.displayName = 'CardDescription';

const CardContent = React.forwardRef<HTMLDivElement, React.HTMLAttributes<HTMLDivElement>>(
    ({ className, ...props }, ref) => (
        <div ref={ref} className={cn('p-6 pt-0', className)} {...props} />
    )
);
CardContent.displayName = 'CardContent';

const CardFooter = React.forwardRef<HTMLDivElement, React.HTMLAttributes<HTMLDivElement>>(
    ({ className, ...props }, ref) => (
        <div
            ref={ref}
            className={cn('flex items-center p-6 pt-0', className)}
            {...props}
        />
    )
);
CardFooter.displayName = 'CardFooter';

// Glass Card Variant
const GlassCard = React.forwardRef<HTMLDivElement, React.HTMLAttributes<HTMLDivElement>>(
    ({ className, ...props }, ref) => (
        <div
            ref={ref}
            className={cn(
                'rounded-2xl border border-white/20 bg-white/10 backdrop-blur-xl text-foreground shadow-xl',
                className
            )}
            {...props}
        />
    )
);
GlassCard.displayName = 'GlassCard';

// Gradient Card Variant
const GradientCard = React.forwardRef<HTMLDivElement, React.HTMLAttributes<HTMLDivElement>>(
    ({ className, ...props }, ref) => (
        <div
            ref={ref}
            className={cn(
                'rounded-2xl bg-gradient-to-br from-primary/10 via-background to-accent/10 border border-primary/10 text-card-foreground shadow-lg',
                className
            )}
            {...props}
        />
    )
);
GradientCard.displayName = 'GradientCard';

// Floating Card with 3D Effect
const FloatingCard = React.forwardRef<HTMLDivElement, React.HTMLAttributes<HTMLDivElement>>(
    ({ className, ...props }, ref) => (
        <div
            ref={ref}
            className={cn(
                'rounded-2xl border bg-card text-card-foreground shadow-xl hover:shadow-2xl hover:-translate-y-2 transition-all duration-500 ease-out',
                className
            )}
            {...props}
        />
    )
);
FloatingCard.displayName = 'FloatingCard';

export {
    Card,
    CardHeader,
    CardFooter,
    CardTitle,
    CardDescription,
    CardContent,
    GlassCard,
    GradientCard,
    FloatingCard,
};
