# SSR desativado

O site voltou a renderizar só no cliente. O PHP entrega o shell Blade e o Vue monta no browser. O SSR do Inertia 3 (`inertia-laravel` 3.1 + `@inertiajs/vite` + Vuetify) foi desligado porque o render no Node estava instável.

Não existia `resources/js/ssr.js`. O plugin reutilizava `resources/js/app.js` e, em produção, gerava o bundle em `bootstrap/ssr`.

## O que saiu

### Docker

Serviço `ssr` (`my-website-ssr`), mesma imagem do `app`:

- comando: `php artisan inertia:start-ssr`
- healthcheck: `php artisan inertia:check-ssr`
- `TZ=America/Manaus` (datas no processo Node)
- rede interna, porta padrão `13714`

O serviço `app` esperava esse container (`depends_on` com `service_started`) e recebia `INERTIA_SSR_URL=http://ssr:13714`. O host `0.0.0.0` no Vite existia para o PHP alcançar o container pela rede Docker, não por localhost.

### Variável de ambiente

`.env.example` tinha `INERTIA_SSR_ENABLED=true`. Ficou `false`.

No `inertia-laravel` 3.1 o padrão do pacote é ligado: `env('INERTIA_SSR_ENABLED', true)`. Sem a variável, o PHP ainda tenta o gateway em `http://127.0.0.1:13714` e, na falha, cai no client em silêncio. Por isso o valor precisa ser `false` de forma explícita. O `phpunit.xml` já forçava `false` e continua assim.

Se `php artisan config:cache` estiver ativo, o valor antigo fica gravado até um novo cache.

### Build

`package.json`:

- `"build": "vite build && vite build --ssr"` voltou para `"build": "vite build"`
- removido o `postbuild` que pedia `docker compose restart ssr`

`/bootstrap/ssr` permanece no `.gitignore` para um bundle antigo não entrar no git.

### Vite

```js
inertia({
    ssr: {
        host: "0.0.0.0",
        sourcemap: false,
    },
}),
ssr: {
    noExternal: ["vuetify"],
},
```

Agora é `inertia({ ssr: false })`. O `noExternal` fazia o bundle Node incluir o Vuetify em vez de exigir o pacote em runtime.

### Vuetify

`createVuetify({ ssr: true })` passou a `ssr: false`. Com `true`, o Vuetify espera hidratar HTML vindo do servidor.

## O que ficou

- `<x-inertia::head />` e `<x-inertia::app />` em `resources/views/app.blade.php`. São o root template do Inertia 3, com ou sem SSR. Sem SSR, o head do componente fica vazio e o título/meta continuam no `@include("partials.meta-tags")`.
- Node na imagem Docker. Já estava no `Dockerfile` antes do SSR; o `inertia:start-ssr` só usava esse binário.
- `@vue/compiler-ssr` no `package-lock.json`. É dependência transitiva do Vue, não um pacote instalado para este SSR.
- Ajustes feitos enquanto o SSR existia, porque ainda valem no client:
  - `createVuetify` é uma factory e recebe `page.props.theme` (cookie `theme` no middleware). O tema `system` quebrava no servidor.
  - Ziggy é aplicado dentro de `withApp`, com `globalThis.Ziggy` e `globalThis.route`, porque `route()` no SSR não via o Ziggy injetado só no browser.
  - `app.provide(TabsKey, createTabsStore())` cria o estado das abas por visita. Um singleton de módulo vazaria entre requests no processo Node.
  - `Theme.js` ignora `document` quando ele não existe.

## Para tentar de novo

1. `INERTIA_SSR_ENABLED=true` e, no container `app`, `INERTIA_SSR_URL=http://ssr:13714`.
2. Recolocar o serviço `ssr` com `inertia:start-ssr`, healthcheck `inertia:check-ssr` e `TZ=America/Manaus`. O `app` precisa subir depois dele.
3. No Vite, voltar o bloco `inertia({ ssr: { host: "0.0.0.0", sourcemap: false } })` e `ssr.noExternal: ["vuetify"]`.
4. `"build": "vite build && vite build --ssr"` e reiniciar o container `ssr` depois do build. Sem o bundle em `bootstrap/ssr`, o Inertia pula o SSR (`ensure_bundle_exists` padrão `true`).
5. Vuetify com `ssr: true`.
6. Manter um único entry (`app.js`), a menos que seja preciso um `setup` manual. Aí entra um `resources/js/ssr.js`.
7. Não usar `window` ou `document` fora de `onMounted`. Falha de SSR cai no client sem erro (`throw_on_error` padrão `false`), então o HTML do servidor pode sumir sem a página quebrar.
