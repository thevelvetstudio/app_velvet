import PhoneInput from 'react-phone-input-2';
import 'react-phone-input-2/lib/style.css';

export default function VelvetPhoneInput({ value, onChange, error }) {
    return (
        <div className="velvet-phone">
            <PhoneInput
                country="co"
                preferredCountries={['co']}
                value={value || ''}
                onChange={(phone) => onChange(phone ? `+${phone.replace(/^\+/, '')}` : '')}
                enableSearch
                disableSearchIcon
                countryCodeEditable={false}
                searchPlaceholder="Buscar país"
                searchNotFound="No se encontraron países"
                inputProps={{ name: 'phone', required: true, autoComplete: 'tel', 'aria-label': 'Número de WhatsApp' }}
            />
            {error && <p className="mt-1 text-xs text-red-400">{error}</p>}
        </div>
    );
}
