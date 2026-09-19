import PhoneInput from 'react-phone-input-2';
import 'react-phone-input-2/lib/style.css';

export default function VelvetPhoneInput({ value, onChange, error }) {
    return <div className="velvet-phone"><PhoneInput country="co" preferredCountries={['co']} value={value} onChange={(phone) => onChange(phone ? `+${phone}` : '')} enableSearch inputProps={{ name: 'phone', required: true, autoComplete: 'tel' }} /><p className="mt-1 text-xs text-red-400">{error}</p></div>;
}
