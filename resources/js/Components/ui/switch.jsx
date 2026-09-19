import * as SwitchPrimitive from '@radix-ui/react-switch';
import { forwardRef } from 'react';

const Switch = forwardRef(({ className = '', ...props }, ref) => (
    <SwitchPrimitive.Root
        ref={ref}
        className={`peer inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full border border-[#4b5060] bg-[#242735] p-0.5 shadow-inner transition-colors outline-none focus-visible:ring-2 focus-visible:ring-[#d56bea] focus-visible:ring-offset-2 focus-visible:ring-offset-[#11131c] disabled:cursor-not-allowed disabled:opacity-50 data-[state=checked]:border-[#d66aeb] data-[state=checked]:bg-[#a92ad8] ${className}`}
        {...props}
    >
        <SwitchPrimitive.Thumb className="pointer-events-none block h-5 w-5 rounded-full bg-white shadow-[0_2px_6px_rgba(0,0,0,.35)] transition-transform duration-200 data-[state=checked]:translate-x-5 data-[state=unchecked]:translate-x-0" />
    </SwitchPrimitive.Root>
));

Switch.displayName = SwitchPrimitive.Root.displayName;

export { Switch };
