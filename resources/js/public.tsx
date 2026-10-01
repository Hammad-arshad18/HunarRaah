import { useState } from 'react';
import { createRoot } from 'react-dom/client';
import * as Select from '@radix-ui/react-select';
import { Check, ChevronDown } from 'lucide-react';

type Option = {value: string; label: string};
function CatalogSelect({id, name, initial, options}: {id: string; name: string; initial: string; options: Option[]}) {
    const [value, setValue] = useState(initial || 'all');
    return <Select.Root value={value} onValueChange={setValue}>
        <input type="hidden" name={name} value={value === 'all' ? '' : value}/>
        <Select.Trigger id={id} className="studio-select-trigger" aria-label="Course format">
            <Select.Value/><Select.Icon><ChevronDown size={18}/></Select.Icon>
        </Select.Trigger>
        <Select.Portal>
            <Select.Content className="studio-select-menu" position="popper" sideOffset={8} align="start" collisionPadding={16}>
                <Select.Viewport>{options.map(option => <Select.Item key={option.value || 'all'} value={option.value || 'all'} className="studio-select-option">
                    <Select.ItemText>{option.label}</Select.ItemText><Select.ItemIndicator><Check size={16}/></Select.ItemIndicator>
                </Select.Item>)}</Select.Viewport>
            </Select.Content>
        </Select.Portal>
    </Select.Root>;
}
for (const native of document.querySelectorAll<HTMLSelectElement>('select[data-studio-select]')) {
    const mount = document.createElement('div');
    const id = native.id + '-enhanced';
    const name = native.name;
    const options = Array.from(native.options, option => ({value: option.value, label: option.textContent || ''}));
    native.before(mount);
    createRoot(mount).render(<CatalogSelect id={id} name={name} initial={native.value} options={options}/>);
    document.querySelector<HTMLLabelElement>('label[for="' + native.id + '"]')?.setAttribute('for', id);
    native.hidden = true;
    native.removeAttribute('name');
}
