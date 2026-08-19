import * as React from 'react';
import { cn } from '@/lib/utils';

export interface InputProps extends React.InputHTMLAttributes<HTMLInputElement> {
    error?: boolean;
    icon?: React.ReactNode;
    iconPosition?: 'left' | 'right';
}

const Input = React.forwardRef<HTMLInputElement, InputProps>(
    ({ className, type, error, icon, iconPosition = 'left', ...props }, ref) => {
        return (
            <div className="relative w-full">
                {icon && iconPosition === 'left' && (
                    <div className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground">
                        {icon}
                    </div>
                )}
                <input
                    type={type}
                    className={cn(
                        'flex h-11 w-full rounded-lg border border-input bg-background px-4 py-2 text-sm ring-offset-background transition-all duration-200',
                        'file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground',
                        'placeholder:text-muted-foreground',
                        'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30 focus-visible:border-primary',
                        'disabled:cursor-not-allowed disabled:opacity-50',
                        'hover:border-primary/50',
                        error && 'border-destructive focus-visible:ring-destructive/30',
                        icon && iconPosition === 'left' && 'pl-10',
                        icon && iconPosition === 'right' && 'pr-10',
                        className
                    )}
                    ref={ref}
                    {...props}
                />
                {icon && iconPosition === 'right' && (
                    <div className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground">
                        {icon}
                    </div>
                )}
            </div>
        );
    }
);
Input.displayName = 'Input';

// Search Input with animations
const SearchInput = React.forwardRef<HTMLInputElement, InputProps>(
    ({ className, ...props }, ref) => {
        const [isFocused, setIsFocused] = React.useState(false);

        return (
            <div
                className={cn(
                    'relative flex items-center rounded-full border border-input bg-background transition-all duration-300',
                    isFocused && 'border-primary shadow-lg shadow-primary/10 ring-2 ring-primary/10',
                    className
                )}
            >
                <div
                    className={cn(
                        'absolute left-4 text-muted-foreground transition-colors duration-200',
                        isFocused && 'text-primary'
                    )}
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="18"
                        height="18"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        strokeWidth="2"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                    >
                        <circle cx="11" cy="11" r="8" />
                        <path d="m21 21-4.3-4.3" />
                    </svg>
                </div>
                <input
                    type="search"
                    className={cn(
                        'flex h-12 w-full bg-transparent pl-12 pr-4 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50'
                    )}
                    ref={ref}
                    onFocus={() => setIsFocused(true)}
                    onBlur={() => setIsFocused(false)}
                    {...props}
                />
            </div>
        );
    }
);
SearchInput.displayName = 'SearchInput';

// Floating Label Input
interface FloatingInputProps extends InputProps {
    label: string;
}

const FloatingInput = React.forwardRef<HTMLInputElement, FloatingInputProps>(
    ({ className, label, id, ...props }, ref) => {
        const [isFocused, setIsFocused] = React.useState(false);
        const [hasValue, setHasValue] = React.useState(false);

        return (
            <div className="relative w-full">
                <input
                    type="text"
                    id={id}
                    className={cn(
                        'peer flex h-14 w-full rounded-lg border border-input bg-background px-4 pt-5 pb-2 text-sm transition-all duration-200',
                        'placeholder-transparent',
                        'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30 focus-visible:border-primary',
                        'disabled:cursor-not-allowed disabled:opacity-50',
                        className
                    )}
                    ref={ref}
                    placeholder={label}
                    onFocus={() => setIsFocused(true)}
                    onBlur={(e) => {
                        setIsFocused(false);
                        setHasValue(e.target.value.length > 0);
                    }}
                    onChange={(e) => setHasValue(e.target.value.length > 0)}
                    {...props}
                />
                <label
                    htmlFor={id}
                    className={cn(
                        'absolute left-4 top-1/2 -translate-y-1/2 text-muted-foreground transition-all duration-200 pointer-events-none',
                        'peer-focus:top-3 peer-focus:text-xs peer-focus:text-primary',
                        (isFocused || hasValue) && 'top-3 text-xs',
                        isFocused && 'text-primary'
                    )}
                >
                    {label}
                </label>
            </div>
        );
    }
);
FloatingInput.displayName = 'FloatingInput';

// OTP Input
interface OTPInputProps {
    length?: number;
    value?: string;
    onChange?: (value: string) => void;
    error?: boolean;
}

const OTPInput = React.forwardRef<HTMLDivElement, OTPInputProps>(
    ({ length = 6, value = '', onChange, error }, ref) => {
        const [otp, setOtp] = React.useState<string[]>(value.split('').slice(0, length));
        const inputRefs = React.useRef<(HTMLInputElement | null)[]>([]);

        React.useEffect(() => {
            if (value) {
                setOtp(value.split('').slice(0, length));
            }
        }, [value, length]);

        const handleChange = (index: number, digit: string) => {
            if (!/^\d*$/.test(digit)) return;

            const newOtp = [...otp];
            newOtp[index] = digit.slice(-1);
            setOtp(newOtp);
            onChange?.(newOtp.join(''));

            // Move to next input
            if (digit && index < length - 1) {
                inputRefs.current[index + 1]?.focus();
            }
        };

        const handleKeyDown = (index: number, e: React.KeyboardEvent<HTMLInputElement>) => {
            if (e.key === 'Backspace' && !otp[index] && index > 0) {
                inputRefs.current[index - 1]?.focus();
            }
        };

        const handlePaste = (e: React.ClipboardEvent) => {
            e.preventDefault();
            const pastedData = e.clipboardData.getData('text').slice(0, length);
            if (!/^\d+$/.test(pastedData)) return;

            const newOtp = pastedData.split('').slice(0, length);
            setOtp(newOtp);
            onChange?.(newOtp.join(''));
            inputRefs.current[Math.min(pastedData.length, length - 1)]?.focus();
        };

        return (
            <div ref={ref} className="flex gap-3 justify-center">
                {Array.from({ length }).map((_, index) => (
                    <input
                        key={index}
                        ref={(el) => (inputRefs.current[index] = el)}
                        type="text"
                        inputMode="numeric"
                        maxLength={1}
                        value={otp[index] || ''}
                        onChange={(e) => handleChange(index, e.target.value)}
                        onKeyDown={(e) => handleKeyDown(index, e)}
                        onPaste={handlePaste}
                        className={cn(
                            'w-12 h-14 text-center text-xl font-semibold rounded-lg border border-input bg-background transition-all duration-200',
                            'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30 focus-visible:border-primary',
                            'hover:border-primary/50',
                            error && 'border-destructive focus-visible:ring-destructive/30',
                            otp[index] && 'border-primary bg-primary/5'
                        )}
                    />
                ))}
            </div>
        );
    }
);
OTPInput.displayName = 'OTPInput';

export { Input, SearchInput, FloatingInput, OTPInput };
