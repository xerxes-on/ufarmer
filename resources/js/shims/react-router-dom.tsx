import { type ComponentProps, type PropsWithChildren } from 'react';
import * as ReactRouterDOM from '../../../node_modules/react-router-dom/dist/index.js';

type BrowserRouterProps = ComponentProps<typeof ReactRouterDOM.BrowserRouter>;

const BrowserRouterShim = ({ children }: PropsWithChildren<BrowserRouterProps>) => (
    <ReactRouterDOM.MemoryRouter initialEntries={['/']}>
        {children}
    </ReactRouterDOM.MemoryRouter>
);

export * from '../../../node_modules/react-router-dom/dist/index.js';
export { BrowserRouterShim as BrowserRouter };
