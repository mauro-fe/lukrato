import {
    resolveSysadminBlogCategoriesEndpoint,
    resolveSysadminBlogPostEndpoint,
    resolveSysadminBlogPostsEndpoint,
    resolveSysadminBlogUploadEndpoint,
} from './sysadmin-blog.js';

describe('admin/api/endpoints/sysadmin-blog', () => {
    it('resolve os endpoints v1 do blog administrativo', () => {
        expect(resolveSysadminBlogPostsEndpoint()).toBe('api/v1/sysadmin/blog/posts');
        expect(resolveSysadminBlogPostEndpoint(15)).toBe('api/v1/sysadmin/blog/posts/15');
        expect(resolveSysadminBlogUploadEndpoint()).toBe('api/v1/sysadmin/blog/upload');
        expect(resolveSysadminBlogCategoriesEndpoint()).toBe('api/v1/sysadmin/blog/categorias');
    });
});