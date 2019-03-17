import {store} from "../store/store";
import * as types from "../store/types";
import router from "./routes";

export default router.beforeEach((to, from, next) => {
    if (to.matched.some(record => record.meta.requireAuth)) {
        if (!store.getters[types.GET_USER_LOGGED_IN]) {
            store.dispatch(types.A_AUTHENTICATE_USER).then((response) => {
                next();
            }, (error) => {
                return router.push({ name: 'login' });
            });
        } else {
            next();
        }
    } else {
        next();
    }
});