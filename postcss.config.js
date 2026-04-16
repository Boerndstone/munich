/**
 * Tailwind v4 (@tailwindcss/postcss) must only run on the Tailwind entry CSS.
 * Other files (Bootstrap SCSS, vendor CSS) only need autoprefixer; running
 * Oxide on every file breaks optional native bindings in some npm installs.
 */
module.exports = (ctx) => {
    const file = ctx.file || '';
    const isAppTailwind =
        file.includes('assets/styles/app.css') || file.includes('assets\\styles\\app.css');

    if (!isAppTailwind) {
        return {
            plugins: {
                autoprefixer: {},
            },
        };
    }

    return {
        plugins: {
            '@tailwindcss/postcss': {},
        },
    };
};
