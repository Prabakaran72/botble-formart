let mix = require('laravel-mix')

const path = require('path')
let directory = path.basename(path.resolve(__dirname))

const source = 'platform/plugins/' + directory
const dist = 'public/vendor/core/plugins/' + directory

mix
    .sass(source + '/resources/assets/sass/testimonial.scss', dist + '/css')
    .js(source + '/resources/assets/js/testimonial.js', dist + '/js')

    .sass(source + '/resources/assets/sass/testimonial-public.scss', dist + '/css')
    .js(source + '/resources/assets/js/testimonial-public.js', dist + '/js')

if (mix.inProduction()) {
    mix
        .copy(dist + '/css/testimonial.css', source + '/public/css')
        .copy(dist + '/css/testimonial-public.css', source + '/public/css')
        .copy(dist + '/js/testimonial.js', source + '/public/js')
        .copy(dist + '/js/testimonial-public.js', source + '/public/js')
}
