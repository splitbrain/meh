/* eslint-disable */
/* tslint:disable */
/**
 * This is an autogenerated file created by the Stencil compiler.
 * It contains typing information for all components that exist in this project.
 */
import { HTMLStencilElement, JSXBase } from "@stencil/core/internal";
export namespace Components {
    interface MehForm {
        /**
          * The base URL for the API If not provided, defaults to "/"
         */
        "api": string;
        /**
          * The post path to associate the comment with If not provided, defaults to the current page path
         */
        "post": string;
    }
    interface MyComponent {
        /**
          * The first name
         */
        "first": string;
        /**
          * The last name
         */
        "last": string;
        /**
          * The middle name
         */
        "middle": string;
    }
}
declare global {
    interface HTMLMehFormElement extends Components.MehForm, HTMLStencilElement {
    }
    var HTMLMehFormElement: {
        prototype: HTMLMehFormElement;
        new (): HTMLMehFormElement;
    };
    interface HTMLMyComponentElement extends Components.MyComponent, HTMLStencilElement {
    }
    var HTMLMyComponentElement: {
        prototype: HTMLMyComponentElement;
        new (): HTMLMyComponentElement;
    };
    interface HTMLElementTagNameMap {
        "meh-form": HTMLMehFormElement;
        "my-component": HTMLMyComponentElement;
    }
}
declare namespace LocalJSX {
    interface MehForm {
        /**
          * The base URL for the API If not provided, defaults to "/"
         */
        "api"?: string;
        /**
          * The post path to associate the comment with If not provided, defaults to the current page path
         */
        "post"?: string;
    }
    interface MyComponent {
        /**
          * The first name
         */
        "first"?: string;
        /**
          * The last name
         */
        "last"?: string;
        /**
          * The middle name
         */
        "middle"?: string;
    }
    interface IntrinsicElements {
        "meh-form": MehForm;
        "my-component": MyComponent;
    }
}
export { LocalJSX as JSX };
declare module "@stencil/core" {
    export namespace JSX {
        interface IntrinsicElements {
            "meh-form": LocalJSX.MehForm & JSXBase.HTMLAttributes<HTMLMehFormElement>;
            "my-component": LocalJSX.MyComponent & JSXBase.HTMLAttributes<HTMLMyComponentElement>;
        }
    }
}
