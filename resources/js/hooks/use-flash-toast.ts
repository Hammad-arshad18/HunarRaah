import { usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';
import type { FlashToast } from '@/types/ui';
export function useFlashToast(): void {
    const { flash } = usePage<{ flash: { toast?: FlashToast } }>().props;
    useEffect(() => {
        if (flash?.toast) toast[flash.toast.type](flash.toast.message);
    }, [flash]);
}
