(function () {
  const { createApp } = Vue;

  const apiBase = window.APP_BOOT.apiBase || '/api.php';

  const CommentTree = {
    name: 'CommentTree',
    props: ['comments', 'currentUser'],
    emits: ['reply', 'edit', 'remove', 'action'],
    methods: {
      canManage(comment) {
        if (!this.currentUser) return false;
        return this.currentUser.role === 'admin' || this.currentUser.id === comment.user_id;
      },
      emitAction(comment, action) {
        this.$emit('action', { comment, action });
      },
    },
    template: `
      <div>
        <div v-for="comment in comments" :key="comment.id" class="comment-node rounded p-3 mt-3 bg-white" :style="{ marginLeft: (comment.depth * 18) + 'px' }">
          <div class="d-flex justify-content-between align-items-start gap-3">
            <div>
              <div class="fw-semibold">{{ comment.author_name }}</div>
              <div class="small text-muted">{{ new Date(comment.created_at).toLocaleString() }}</div>
            </div>
            <span class="badge text-bg-light">Lv.{{ comment.depth }}</span>
          </div>
          <div class="comment-content mt-2">{{ comment.content }}</div>
          <div class="comment-actions mt-3">
            <button type="button" class="btn btn-sm btn-outline-primary" @click="$emit('reply', comment)">답글</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" @click="emitAction(comment, 'follow')">팔로우</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" @click="emitAction(comment, 'block')">차단</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" @click="emitAction(comment, 'report')">신고</button>
            <button v-if="canManage(comment)" type="button" class="btn btn-sm btn-outline-dark" @click="$emit('edit', comment)">수정</button>
            <button v-if="canManage(comment)" type="button" class="btn btn-sm btn-outline-danger" @click="$emit('remove', comment)">삭제</button>
          </div>
          <comment-tree
            v-if="comment.children && comment.children.length"
            :comments="comment.children"
            :current-user="currentUser"
            @reply="$emit('reply', $event)"
            @edit="$emit('edit', $event)"
            @remove="$emit('remove', $event)"
            @action="$emit('action', $event)"
          />
        </div>
      </div>
    `,
  };

  createApp({
    components: {
      CommentTree,
    },
    data() {
      return {
        route: window.APP_BOOT.route || '/',
        csrfToken: window.APP_BOOT.csrfToken || '',
        appName: window.APP_BOOT.appName || 'Community Site',
        boot: {
          currentUser: null,
          menu: [],
          stats: { members: 0, posts: 0, comments: 0 },
          sidebar: { recentPosts: [], recentComments: [], recentPhotos: [] },
        },
        listData: { category: null, posts: { items: [], pagination: { page: 1, pages: 1, total: 0 } } },
        postData: null,
        comments: [],
        adminCategories: [],
        profile: null,
        loginForm: { identity: '', password: '' },
        registerForm: { email: '', username: '', display_name: '', password: '', bio: '' },
        profileForm: { email: '', username: '', display_name: '', bio: '' },
        categoryForm: { name: '', slug: '', parent_id: '', sort_order: 0, is_enabled: true, description: '' },
        postForm: { id: null, category_id: '', title: '', content: '' },
        commentForm: { content: '', parent_id: '' },
        activeReplyTo: null,
        editingComment: null,
        notice: '',
        error: '',
        loading: false,
      };
    },
    computed: {
      currentView() {
        if (this.route.startsWith('/post/') && this.route.endsWith('/edit')) return 'post-edit';
        if (this.route.startsWith('/post/')) return 'post-read';
        if (this.route.startsWith('/category/')) return 'category';
        if (this.route === '/login') return 'login';
        if (this.route === '/register') return 'register';
        if (this.route === '/profile') return 'profile';
        if (this.route === '/write') return 'write';
        if (this.route === '/admin/categories') return 'admin-categories';
        return 'home';
      },
      postId() {
        const match = this.route.match(/^\/post\/(\d+)/);
        return match ? Number(match[1]) : null;
      },
      categorySlug() {
        const match = this.route.match(/^\/category\/([^/]+)/);
        return match ? decodeURIComponent(match[1]) : null;
      },
      isAuthenticated() {
        return !!this.boot.currentUser;
      },
      isAdmin() {
        return this.boot.currentUser && this.boot.currentUser.role === 'admin';
      },
    },
    mounted() {
      window.addEventListener('popstate', this.handlePopState);
      this.loadBootstrap().then(() => this.loadRoute());
    },
    beforeUnmount() {
      window.removeEventListener('popstate', this.handlePopState);
    },
    methods: {
      async api(route, options = {}) {
        const method = (options.method || 'GET').toUpperCase();
        const headers = Object.assign({}, options.headers || {});
        let body = options.body;
        const query = options.query || {};

        if (!(body instanceof FormData) && body && typeof body === 'object' && method !== 'GET') {
          headers['Content-Type'] = 'application/json';
          body = JSON.stringify(body);
        }

        if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(method)) {
          headers['X-CSRF-Token'] = this.csrfToken;
        }

        const queryString = new URLSearchParams({ route, ...query }).toString();
        const url = `${apiBase}?${queryString}`;
        const response = await fetch(url, {
          method,
          credentials: 'same-origin',
          headers,
          body,
        });

        const payload = await response.json().catch(() => ({ ok: false, message: 'Invalid JSON response.' }));
        if (!response.ok || payload.ok === false) {
          throw new Error(payload.message || `Request failed with status ${response.status}`);
        }

        return payload.data;
      },
      async loadBootstrap() {
        this.boot = await this.api('/');
      },
      async loadRoute() {
        this.notice = '';
        this.error = '';
        this.loading = true;

        try {
          if (this.currentView === 'home') {
            this.listData = await this.fetchPosts();
          }
          if (this.currentView === 'category') {
            this.listData = await this.fetchPosts(this.categorySlug);
          }
          if (this.currentView === 'post-read') {
            await this.loadPost(this.postId);
          }
          if (this.currentView === 'post-edit') {
            await this.loadPost(this.postId);
            this.postForm = {
              id: this.postData.id,
              category_id: String(this.postData.category_id),
              title: this.postData.title,
              content: this.postData.content,
            };
          }
          if (this.currentView === 'profile' && this.isAuthenticated) {
            await this.loadProfile();
          }
          if (this.currentView === 'admin-categories' && this.isAdmin) {
            await this.loadAdminCategories();
          }
          if (this.currentView === 'write') {
            this.postForm = { id: null, category_id: '', title: '', content: '' };
          }
        } catch (error) {
          this.error = error.message;
        } finally {
          this.loading = false;
        }
      },
      async fetchPosts(categorySlug = null) {
        return this.api('/posts', {
          method: 'GET',
          query: categorySlug ? { category_slug: categorySlug } : {},
        });
      },
      async loadPost(id) {
        if (!id) return;
        this.postData = await this.api(`/posts/${id}`);
        this.comments = await this.api(`/posts/${id}/comments`);
      },
      async loadProfile() {
        this.profile = await this.api('/profile');
        this.profileForm = {
          email: this.profile.email,
          username: this.profile.username,
          display_name: this.profile.displayName,
          bio: this.profile.bio || '',
        };
      },
      async loadAdminCategories() {
        this.adminCategories = await this.api('/admin/categories');
      },
      navigate(path) {
        history.pushState({}, '', path);
        this.route = path;
        this.loadRoute();
      },
      handlePopState() {
        this.route = window.location.pathname;
        this.loadRoute();
      },
      routeClick(path, event) {
        if (event) event.preventDefault();
        this.navigate(path);
      },
      async login() {
        try {
          const user = await this.api('/auth/login', { method: 'POST', body: this.loginForm });
          this.boot.currentUser = user;
          this.notice = '로그인되었습니다.';
          this.loginForm = { identity: '', password: '' };
          await this.loadBootstrap();
          if (this.currentView === 'login') this.navigate('/');
        } catch (error) {
          this.error = error.message;
        }
      },
      async register() {
        try {
          const user = await this.api('/auth/register', { method: 'POST', body: this.registerForm });
          this.boot.currentUser = user;
          this.notice = '회원가입이 완료되었습니다.';
          await this.loadBootstrap();
          this.navigate('/');
        } catch (error) {
          this.error = error.message;
        }
      },
      async logout() {
        try {
          await this.api('/auth/logout', { method: 'POST', body: {} });
          this.boot.currentUser = null;
          this.notice = '로그아웃되었습니다.';
          await this.loadBootstrap();
          this.navigate('/');
        } catch (error) {
          this.error = error.message;
        }
      },
      async saveProfile() {
        try {
          this.profile = await this.api('/profile', { method: 'POST', body: this.profileForm });
          this.boot.currentUser = this.profile;
          this.notice = '프로필이 수정되었습니다.';
          await this.loadBootstrap();
        } catch (error) {
          this.error = error.message;
        }
      },
      async uploadProfilePhoto(event) {
        const file = event.target.files[0];
        if (!file) return;

        const form = new FormData();
        form.append('photo', file);

        try {
          this.profile = await this.api('/profile/photo', { method: 'POST', body: form });
          this.boot.currentUser = this.profile;
          this.notice = '프로필 사진이 업로드되었습니다.';
          await this.loadBootstrap();
        } catch (error) {
          this.error = error.message;
        }
      },
      async saveCategory() {
        try {
          await this.api('/admin/categories', { method: 'POST', body: this.categoryForm });
          this.notice = '카테고리가 생성되었습니다.';
          this.categoryForm = { name: '', slug: '', parent_id: '', sort_order: 0, is_enabled: true, description: '' };
          await this.loadBootstrap();
          await this.loadAdminCategories();
        } catch (error) {
          this.error = error.message;
        }
      },
      async toggleCategory(category) {
        try {
          await this.api(`/admin/categories/${category.id}`, {
            method: 'PUT',
            body: {
              name: category.name,
              slug: category.slug,
              parent_id: category.parentId || '',
              sort_order: category.sortOrder,
              is_enabled: !category.isEnabled,
            },
          });
          this.notice = '카테고리 상태를 변경했습니다.';
          await this.loadBootstrap();
          await this.loadAdminCategories();
        } catch (error) {
          this.error = error.message;
        }
      },
      async removeCategory(category) {
        if (!confirm('카테고리를 삭제하시겠습니까?')) return;
        try {
          await this.api(`/admin/categories/${category.id}`, { method: 'DELETE', body: {} });
          this.notice = '카테고리가 삭제되었습니다.';
          await this.loadBootstrap();
          await this.loadAdminCategories();
        } catch (error) {
          this.error = error.message;
        }
      },
      async savePost() {
        try {
          if (this.postForm.id) {
            await this.api(`/posts/${this.postForm.id}`, { method: 'PUT', body: this.postForm });
            this.notice = '게시글이 수정되었습니다.';
            this.navigate(`/post/${this.postForm.id}`);
          } else {
            const post = await this.api('/posts', { method: 'POST', body: this.postForm });
            this.notice = '게시글이 작성되었습니다.';
            this.navigate(`/post/${post.id}`);
          }
          await this.loadBootstrap();
        } catch (error) {
          this.error = error.message;
        }
      },
      async removePost() {
        if (!this.postData || !confirm('게시글을 삭제하시겠습니까?')) return;
        try {
          await this.api(`/posts/${this.postData.id}`, { method: 'DELETE', body: {} });
          this.notice = '게시글이 삭제되었습니다.';
          await this.loadBootstrap();
          this.navigate('/');
        } catch (error) {
          this.error = error.message;
        }
      },
      async postAction(action) {
        if (!this.postData) return;
        try {
          await this.api(`/posts/${this.postData.id}/actions/${action}`, { method: 'POST', body: { reason: '' } });
          this.notice = `게시글 ${action} 처리되었습니다.`;
        } catch (error) {
          this.error = error.message;
        }
      },
      async submitComment() {
        if (!this.postData) return;
        const payload = {
          content: this.commentForm.content,
          parent_id: this.commentForm.parent_id || '',
        };

        try {
          await this.api(`/posts/${this.postData.id}/comments`, { method: 'POST', body: payload });
          this.notice = '댓글이 등록되었습니다.';
          this.commentForm = { content: '', parent_id: '' };
          this.activeReplyTo = null;
          this.comments = await this.api(`/posts/${this.postData.id}/comments`);
          await this.loadBootstrap();
        } catch (error) {
          this.error = error.message;
        }
      },
      replyTo(comment) {
        this.activeReplyTo = comment;
        this.commentForm.parent_id = comment.id;
        this.commentForm.content = '';
      },
      async editComment(comment) {
        const content = prompt('댓글을 수정하세요.', comment.content);
        if (content === null) return;
        try {
          await this.api(`/comments/${comment.id}`, { method: 'PUT', body: { content } });
          this.notice = '댓글이 수정되었습니다.';
          this.comments = await this.api(`/posts/${this.postData.id}/comments`);
        } catch (error) {
          this.error = error.message;
        }
      },
      async removeComment(comment) {
        if (!confirm('댓글을 삭제하시겠습니까?')) return;
        try {
          await this.api(`/comments/${comment.id}`, { method: 'DELETE', body: {} });
          this.notice = '댓글이 삭제되었습니다.';
          this.comments = await this.api(`/posts/${this.postData.id}/comments`);
          await this.loadBootstrap();
        } catch (error) {
          this.error = error.message;
        }
      },
      async commentAction(payloadOrComment, maybeAction) {
        const comment = payloadOrComment.comment || payloadOrComment;
        const action = payloadOrComment.action || maybeAction;
        try {
          await this.api(`/comments/${comment.id}/actions/${action}`, { method: 'POST', body: { reason: '' } });
          this.notice = `댓글 ${action} 처리되었습니다.`;
        } catch (error) {
          this.error = error.message;
        }
      },
      canManagePost() {
        if (!this.postData || !this.boot.currentUser) return false;
        return this.boot.currentUser.role === 'admin' || this.boot.currentUser.id === this.postData.user_id;
      },
    },
    template: `
      <div class="site-shell">
        <header class="border-bottom border-dark-subtle bg-light sticky-top shadow-sm">
          <div class="container py-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
              <a href="/" class="site-brand text-decoration-none text-dark" @click="routeClick('/')">{{ appName }}</a>
              <div class="small text-muted">순수 PHP · PostgreSQL · Bootstrap · Vue CDN</div>
            </div>
          </div>
          <nav class="menu-bar">
            <div class="container">
              <ul class="nav">
                <li class="nav-item"><a href="/" class="nav-link route-link" @click="routeClick('/')">홈</a></li>
                <li v-for="category in boot.menu" :key="category.id" class="nav-item dropdown position-relative menu-hover">
                  <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">{{ category.name }}</a>
                  <ul class="dropdown-menu">
                    <li><a href="#" class="dropdown-item" @click.prevent="navigate('/category/' + category.slug)">{{ category.name }} 전체</a></li>
                    <li v-for="child in category.children" :key="child.id">
                      <a href="#" class="dropdown-item" @click.prevent="navigate('/category/' + child.slug)">{{ child.name }}</a>
                    </li>
                  </ul>
                </li>
                <li class="nav-item" v-if="isAuthenticated"><a href="/write" class="nav-link route-link" @click="routeClick('/write')">글쓰기</a></li>
                <li class="nav-item" v-if="isAdmin"><a href="/admin/categories" class="nav-link route-link" @click="routeClick('/admin/categories')">메뉴관리</a></li>
              </ul>
            </div>
          </nav>
        </header>

        <main class="container py-4">
          <div class="hero-banner p-4 mb-4">
            <div class="row g-3 align-items-center">
              <div class="col-lg-8">
                <h1 class="h3 mb-2">회원 관리 + 게시판 + 2단 메뉴 사이트</h1>
                <p class="mb-0 text-white-50">모든 화면은 index.php에서 진입하고, 데이터 처리는 api.php를 통해 수행됩니다.</p>
              </div>
              <div class="col-lg-4 text-lg-end">
                <span class="badge text-bg-light me-2">회원 {{ boot.stats.members }}</span>
                <span class="badge text-bg-light me-2">글 {{ boot.stats.posts }}</span>
                <span class="badge text-bg-light">댓글 {{ boot.stats.comments }}</span>
              </div>
            </div>
          </div>

          <div v-if="notice" class="alert alert-success">{{ notice }}</div>
          <div v-if="error" class="alert alert-danger">{{ error }}</div>

          <div class="row g-4">
            <aside class="col-lg-3 site-side-left">
              <div class="panel-card card">
                <div class="card-header">사이트 통계</div>
                <div class="card-body">
                  <ul class="list-unstyled mb-0">
                    <li class="mb-2">회원수: <strong>{{ boot.stats.members }}</strong></li>
                    <li class="mb-2">글 수: <strong>{{ boot.stats.posts }}</strong></li>
                    <li>댓글 수: <strong>{{ boot.stats.comments }}</strong></li>
                  </ul>
                </div>
              </div>
            </aside>

            <section class="col-lg-6 site-center">
              <div class="panel-card card">
                <div class="card-body">
                  <div v-if="loading" class="text-center py-5">로딩 중...</div>

                  <template v-else>
                    <template v-if="currentView === 'home' || currentView === 'category'">
                      <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                          <h2 class="h4 mb-1">{{ listData.category ? listData.category.name : '전체 게시글' }}</h2>
                          <p class="text-muted mb-0">카테고리별 게시글 목록입니다.</p>
                        </div>
                        <button v-if="isAuthenticated" class="btn btn-primary" @click="navigate('/write')">글쓰기</button>
                      </div>
                      <div v-if="!listData.posts.items.length" class="text-muted">등록된 글이 없습니다.</div>
                      <div v-else class="list-group">
                        <a
                          v-for="post in listData.posts.items"
                          :key="post.id"
                          href="#"
                          class="list-group-item list-group-item-action"
                          @click.prevent="navigate('/post/' + post.id)"
                        >
                          <div class="d-flex justify-content-between gap-3">
                            <div>
                              <div class="fw-semibold">{{ post.title }}</div>
                              <div class="small text-muted">{{ post.category_name }} · {{ post.author_name }}</div>
                            </div>
                            <div class="small text-end text-muted">
                              <div>조회 {{ post.view_count }}</div>
                              <div>댓글 {{ post.comment_count }}</div>
                            </div>
                          </div>
                        </a>
                      </div>
                    </template>

                    <template v-else-if="currentView === 'post-read' && postData">
                      <div class="mb-3">
                        <div class="small text-muted mb-2">{{ postData.category_name }} · {{ postData.author_name }}</div>
                        <h2 class="h3">{{ postData.title }}</h2>
                        <div class="small text-muted">조회 {{ postData.view_count }} · 댓글 {{ postData.comment_count }} · {{ new Date(postData.created_at).toLocaleString() }}</div>
                      </div>
                      <div class="post-body border rounded p-3 bg-light">{{ postData.content }}</div>
                      <div class="post-actions mt-3">
                        <button class="btn btn-sm btn-outline-primary" @click="document.getElementById('comment-form').scrollIntoView({ behavior: 'smooth' })">댓글</button>
                        <button v-if="canManagePost()" class="btn btn-sm btn-outline-dark" @click="navigate('/post/' + postData.id + '/edit')">수정</button>
                        <button v-if="canManagePost()" class="btn btn-sm btn-outline-danger" @click="removePost">삭제</button>
                        <button class="btn btn-sm btn-outline-secondary" @click="postAction('report')">신고</button>
                        <button class="btn btn-sm btn-outline-secondary" @click="postAction('block')">차단</button>
                        <button class="btn btn-sm btn-outline-secondary" @click="postAction('follow')">팔로우</button>
                      </div>

                      <div id="comment-form" class="mt-4">
                        <h3 class="h5">댓글 작성</h3>
                        <div v-if="activeReplyTo" class="small text-muted mb-2">답글 대상: {{ activeReplyTo.author_name }}</div>
                        <textarea v-model="commentForm.content" class="form-control mb-2" rows="4" placeholder="댓글을 입력하세요."></textarea>
                        <div class="d-flex gap-2">
                          <button class="btn btn-primary" :disabled="!isAuthenticated" @click="submitComment">댓글 등록</button>
                          <button v-if="activeReplyTo" class="btn btn-outline-secondary" @click="activeReplyTo = null; commentForm.parent_id = ''">답글 취소</button>
                        </div>
                        <div v-if="!isAuthenticated" class="small text-muted mt-2">댓글 작성은 로그인 후 가능합니다.</div>
                      </div>

                      <div class="mt-4">
                        <h3 class="h5">댓글 트리</h3>
                        <comment-tree
                          :comments="comments"
                          :current-user="boot.currentUser"
                          @reply="replyTo"
                          @edit="editComment"
                          @remove="removeComment"
                          @action="commentAction"
                        />
                      </div>
                    </template>

                    <template v-else-if="currentView === 'login'">
                      <h2 class="h4 mb-3">로그인</h2>
                      <div class="mb-3">
                        <label class="form-label">이메일 또는 아이디</label>
                        <input v-model="loginForm.identity" class="form-control">
                      </div>
                      <div class="mb-3">
                        <label class="form-label">비밀번호</label>
                        <input v-model="loginForm.password" type="password" class="form-control">
                      </div>
                      <button class="btn btn-primary" @click="login">로그인</button>
                    </template>

                    <template v-else-if="currentView === 'register'">
                      <h2 class="h4 mb-3">회원 가입</h2>
                      <div class="row g-3">
                        <div class="col-md-6"><input v-model="registerForm.email" class="form-control" placeholder="이메일"></div>
                        <div class="col-md-6"><input v-model="registerForm.username" class="form-control" placeholder="아이디"></div>
                        <div class="col-md-6"><input v-model="registerForm.display_name" class="form-control" placeholder="닉네임"></div>
                        <div class="col-md-6"><input v-model="registerForm.password" type="password" class="form-control" placeholder="비밀번호"></div>
                        <div class="col-12"><textarea v-model="registerForm.bio" class="form-control" rows="4" placeholder="자기소개"></textarea></div>
                      </div>
                      <button class="btn btn-primary mt-3" @click="register">회원 가입</button>
                    </template>

                    <template v-else-if="currentView === 'profile' && profile">
                      <h2 class="h4 mb-3">프로필 수정</h2>
                      <div v-if="profile.profilePhotoUrl" class="mb-3">
                        <img :src="profile.profilePhotoUrl" class="img-fluid rounded" style="max-width: 200px;">
                      </div>
                      <div class="row g-3">
                        <div class="col-md-6"><input v-model="profileForm.email" class="form-control" placeholder="이메일"></div>
                        <div class="col-md-6"><input v-model="profileForm.username" class="form-control" placeholder="아이디"></div>
                        <div class="col-md-6"><input v-model="profileForm.display_name" class="form-control" placeholder="닉네임"></div>
                        <div class="col-12"><textarea v-model="profileForm.bio" class="form-control" rows="4" placeholder="자기소개"></textarea></div>
                      </div>
                      <div class="mt-3 d-flex flex-wrap gap-2">
                        <button class="btn btn-primary" @click="saveProfile">저장</button>
                        <input type="file" accept="image/*" class="form-control" style="max-width: 320px;" @change="uploadProfilePhoto">
                      </div>
                    </template>

                    <template v-else-if="currentView === 'write' || currentView === 'post-edit'">
                      <h2 class="h4 mb-3">{{ currentView === 'write' ? '게시글 작성' : '게시글 수정' }}</h2>
                      <div class="mb-3">
                        <label class="form-label">카테고리</label>
                        <select v-model="postForm.category_id" class="form-select">
                          <option value="">카테고리 선택</option>
                          <optgroup v-for="category in boot.menu" :key="category.id" :label="category.name">
                            <option :value="String(category.id)">{{ category.name }}</option>
                            <option v-for="child in category.children" :key="child.id" :value="String(child.id)">└ {{ child.name }}</option>
                          </optgroup>
                        </select>
                      </div>
                      <div class="mb-3"><input v-model="postForm.title" class="form-control" placeholder="제목"></div>
                      <div class="mb-3"><textarea v-model="postForm.content" class="form-control" rows="10" placeholder="내용"></textarea></div>
                      <button class="btn btn-primary" @click="savePost">저장</button>
                    </template>

                    <template v-else-if="currentView === 'admin-categories'">
                      <h2 class="h4 mb-3">메뉴 관리</h2>
                      <div class="row g-3">
                        <div class="col-md-6"><input v-model="categoryForm.name" class="form-control" placeholder="카테고리명"></div>
                        <div class="col-md-6"><input v-model="categoryForm.slug" class="form-control" placeholder="슬러그"></div>
                        <div class="col-md-6">
                          <select v-model="categoryForm.parent_id" class="form-select">
                            <option value="">1차 카테고리</option>
                            <option v-for="category in boot.menu" :key="category.id" :value="category.id">{{ category.name }}</option>
                          </select>
                        </div>
                        <div class="col-md-3"><input v-model="categoryForm.sort_order" type="number" class="form-control" placeholder="정렬"></div>
                        <div class="col-md-3" class="d-flex align-items-center"><div class="form-check mt-2"><input v-model="categoryForm.is_enabled" class="form-check-input" type="checkbox" id="isEnabled"><label class="form-check-label" for="isEnabled">노출</label></div></div>
                        <div class="col-12"><textarea v-model="categoryForm.description" class="form-control" rows="3" placeholder="설명"></textarea></div>
                      </div>
                      <button class="btn btn-primary mt-3" @click="saveCategory">카테고리 생성</button>

                      <div class="mt-4">
                        <div v-for="category in adminCategories" :key="category.id" class="border rounded p-3 mb-3">
                          <div class="d-flex justify-content-between align-items-center">
                            <div><strong>{{ category.name }}</strong> <span class="text-muted small">/{{ category.slug }}</span></div>
                            <div class="d-flex gap-2">
                              <button class="btn btn-sm btn-outline-secondary" @click="toggleCategory(category)">{{ category.isEnabled ? '숨김' : '노출' }}</button>
                              <button class="btn btn-sm btn-outline-danger" @click="removeCategory(category)">삭제</button>
                            </div>
                          </div>
                          <div v-if="category.children.length" class="mt-3 ms-3">
                            <div v-for="child in category.children" :key="child.id" class="border rounded p-2 mb-2 bg-light">
                              <div class="d-flex justify-content-between align-items-center">
                                <div>└ {{ child.name }} <span class="text-muted small">/{{ child.slug }}</span></div>
                                <div class="d-flex gap-2">
                                  <button class="btn btn-sm btn-outline-secondary" @click="toggleCategory(child)">{{ child.isEnabled ? '숨김' : '노출' }}</button>
                                  <button class="btn btn-sm btn-outline-danger" @click="removeCategory(child)">삭제</button>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </template>
                  </template>
                </div>
              </div>
            </section>

            <aside class="col-lg-3 site-side-right">
              <div class="panel-card card mb-4">
                <div class="card-header">로그인</div>
                <div class="card-body">
                  <template v-if="!isAuthenticated">
                    <input v-model="loginForm.identity" class="form-control mb-2" placeholder="이메일 또는 아이디">
                    <input v-model="loginForm.password" type="password" class="form-control mb-2" placeholder="비밀번호">
                    <div class="d-grid gap-2">
                      <button class="btn btn-primary" @click="login">로그인</button>
                      <button class="btn btn-outline-primary" @click="navigate('/register')">회원가입</button>
                    </div>
                  </template>
                  <template v-else>
                    <div class="d-flex align-items-center gap-3">
                      <img v-if="boot.currentUser.profilePhotoUrl" :src="boot.currentUser.profilePhotoUrl" class="rounded-circle" width="56" height="56">
                      <div>
                        <div class="fw-semibold">{{ boot.currentUser.displayName }}</div>
                        <div class="small text-muted">{{ boot.currentUser.role }}</div>
                      </div>
                    </div>
                    <div class="d-grid gap-2 mt-3">
                      <button class="btn btn-outline-primary" @click="navigate('/profile')">내 정보</button>
                      <button class="btn btn-outline-secondary" @click="logout">로그아웃</button>
                    </div>
                  </template>
                </div>
              </div>

              <div class="panel-card card mb-4">
                <div class="card-header">최근 글</div>
                <ul class="list-group list-group-flush sidebar-list">
                  <li v-for="item in boot.sidebar.recentPosts" :key="item.id" class="list-group-item">
                    <a href="#" @click.prevent="navigate('/post/' + item.id)">{{ item.title }}</a>
                  </li>
                </ul>
              </div>

              <div class="panel-card card mb-4">
                <div class="card-header">최근 댓글</div>
                <ul class="list-group list-group-flush sidebar-list">
                  <li v-for="item in boot.sidebar.recentComments" :key="item.id" class="list-group-item">
                    <a href="#" @click.prevent="navigate('/post/' + item.post_id)">#{{ item.post_id }} {{ item.content }}</a>
                  </li>
                </ul>
              </div>

              <div class="panel-card card">
                <div class="card-header">최근 사진</div>
                <div class="card-body">
                  <div class="row g-2">
                    <div v-for="photo in boot.sidebar.recentPhotos" :key="photo.id" class="col-6">
                      <img :src="photo.profile_photo_path" class="photo-thumb" :alt="photo.display_name">
                    </div>
                  </div>
                </div>
              </div>
            </aside>
          </div>
        </main>
      </div>
    `,
  }).mount('#app');
})();
