import React from "react";
import { createRoot } from "react-dom/client";
import { createInertiaApp } from "@inertiajs/react";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import WorkshopLayout from "./Layouts/WorkshopLayout";
import "../../css/workshop.css";

createInertiaApp({
    title: (title) => `${title} · Garage Manager`,
    resolve: async (name) => {
        const page = await resolvePageComponent(
            `./Pages/${name}.jsx`,
            import.meta.glob("./Pages/**/*.jsx"),
        );
        page.default.layout ??= (page) => (
            <WorkshopLayout>{page}</WorkshopLayout>
        );
        return page;
    },
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
    progress: { color: "#3659d9", showSpinner: false },
});
