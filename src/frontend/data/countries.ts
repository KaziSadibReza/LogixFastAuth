import { getCountries, getCountryCallingCode } from 'libphonenumber-js';

export interface CountryOption {
  code: string;
  name: string;
  dial: string;
  flag: string;
}

function countryFlag(code: string): string {
  return code
    .toUpperCase()
    .replace(/./g, (char) => String.fromCodePoint(127397 + char.charCodeAt(0)));
}

const regionNames = new Intl.DisplayNames(['en'], { type: 'region' });

export const COUNTRIES: CountryOption[] = getCountries()
  .map((code) => ({
    code,
    name: regionNames.of(code) || code,
    dial: `+${getCountryCallingCode(code)}`,
    flag: countryFlag(code),
  }))
  .sort((a, b) => a.name.localeCompare(b.name));

export function findCountry(code: string): CountryOption | undefined {
  return COUNTRIES.find((c) => c.code === code);
}
