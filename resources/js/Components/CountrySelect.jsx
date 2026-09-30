import Select, { components } from 'react-select';
import ReactCountryFlag from 'react-country-flag';

function CountryFlag({ code, label }) {
    return <ReactCountryFlag countryCode={code} svg title={label} aria-label={label} style={{ width: '1.35em', height: '1.35em', display: 'inline-block', verticalAlign: 'middle' }} />;
}

function CountryOption({ data, ...props }) {
    return <components.Option {...props}><span className="mr-2 inline-flex items-center"><CountryFlag code={data.code} label={data.label} /></span>{data.label}</components.Option>;
}

function CountrySingleValue({ data, ...props }) {
    return <components.SingleValue {...props}><span className="mr-2 inline-flex items-center"><CountryFlag code={data.code} label={data.label} /></span>{data.label}</components.SingleValue>;
}

const styles = {
    container: (base) => ({ ...base, marginTop: '.55rem' }),
    control: (base, state) => ({ ...base, minHeight: '3rem', border: `1px solid ${state.isFocused ? '#c23bea' : '#353544'}`, borderRadius: '.5rem', backgroundColor: '#11121a', boxShadow: state.isFocused ? '0 0 0 1px #c23bea' : 'none', cursor: 'pointer', transition: 'border-color .18s ease, box-shadow .18s ease' }),
    valueContainer: (base) => ({ ...base, padding: '0 .85rem' }),
    singleValue: (base) => ({ ...base, color: '#f7f1fb', display: 'flex', alignItems: 'center', fontSize: '.875rem' }),
    input: (base) => ({ ...base, color: '#f7f1fb', fontSize: '.875rem' }),
    menu: (base) => ({ ...base, zIndex: 50, overflow: 'hidden', marginTop: '.35rem', border: '1px solid #3d2f46', borderRadius: '.5rem', backgroundColor: '#151522', boxShadow: '0 18px 40px rgba(0, 0, 0, .35)' }),
    menuList: (base) => ({ ...base, padding: '.3rem' }),
    option: (base, state) => ({ ...base, display: 'flex', alignItems: 'center', minHeight: '2.5rem', borderRadius: '.35rem', backgroundColor: state.isSelected ? '#55166e' : state.isFocused ? '#2b1735' : 'transparent', color: '#f2edf5', cursor: 'pointer', fontSize: '.875rem' }),
    placeholder: (base) => ({ ...base, color: '#777d8f', fontSize: '.875rem' }),
    dropdownIndicator: (base) => ({ ...base, color: '#777d8f', padding: '0 .75rem' }),
    indicatorSeparator: () => ({ display: 'none' }),
};

export default function CountrySelect({ value, onChange, options, error }) {
    const selected = options.find((option) => option.value === value) || null;

    return <label className="apply-country-field block text-xs font-medium text-gray-200">País<Select classNamePrefix="velvet-country-select" value={selected} options={options} onChange={(option) => onChange({ target: { value: option?.value || '' } })} isSearchable={false} placeholder="Selecciona un país" components={{ Option: CountryOption, SingleValue: CountrySingleValue }} styles={styles} />{error && <span className="mt-1 block text-xs text-red-400">{error}</span>}</label>;
}
