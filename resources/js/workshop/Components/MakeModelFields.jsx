import React, { useId } from "react";
import { Field } from "./ui";
import { compatibleModels, modelAfterMakeChange } from "./makeModel";
export default function MakeModelFields({ form, modelsByMake }) {
    const id = useId();
    const models = compatibleModels(modelsByMake, form.data.make || "");
    return (
        <>
            <Field
                label="Μάρκα"
                name="make"
                form={form}
                list={`makes-${id}`}
                onChange={(e) => {
                    const value = e.target.value;
                    form.setData({
                        ...form.data,
                        make: value,
                        model: modelAfterMakeChange(
                            modelsByMake,
                            form.data.make,
                            form.data.model,
                            value,
                        ),
                    });
                }}
            />
            <datalist id={`makes-${id}`}>
                {Object.keys(modelsByMake).map((make) => (
                    <option value={make} key={make} />
                ))}
            </datalist>
            <Field
                label="Μοντέλο"
                name="model"
                form={form}
                list={`models-${id}`}
                disabled={!form.data.make.trim()}
                hint="Επιλέξτε πρόταση ή γράψτε μοντέλο που δεν υπάρχει στον κατάλογο."
            />
            <datalist id={`models-${id}`}>
                {models.map((model) => (
                    <option key={model} value={model} />
                ))}
            </datalist>
        </>
    );
}
