function encodeEndpointSegment(value) {
    return encodeURIComponent(String(value));
}

export function resolveSysadminBlogPostsEndpoint() {
    return 'api/v1/sysadmin/blog/posts';
}

export function resolveSysadminBlogPostEndpoint(postId) {
    return `${resolveSysadminBlogPostsEndpoint()}/${encodeEndpointSegment(postId)}`;
}

export function resolveSysadminBlogUploadEndpoint() {
    return 'api/v1/sysadmin/blog/upload';
}

export function resolveSysadminBlogCategoriesEndpoint() {
    return 'api/v1/sysadmin/blog/categorias';
}