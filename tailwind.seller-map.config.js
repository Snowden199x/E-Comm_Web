// Scoped Tailwind build for the address-map component on seller pages.
// No global reset (preflight is off), so it cannot restyle the rest of the seller area.
/** @type {import('tailwindcss').Config} */
export default {
    content: ['./resources/views/components/address-map.blade.php'],
    corePlugins: { preflight: false },
    theme: { extend: {} },
    plugins: [],
};
