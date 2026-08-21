(function () {
  "use strict";

  const config = window.npcinkMyComments;
  const list = document.getElementById("npcink-my-comments-list");
  if (!config || !list) return;

  const state = { page: 1, totalPages: 0, status: "all", selected: new Set() };
  const feedback = document.getElementById("npcink-my-comments-feedback");
  const pageLabel = document.getElementById("npcink-my-comments-page");
  const prevButton = document.getElementById("npcink-my-comments-prev");
  const nextButton = document.getElementById("npcink-my-comments-next");
  const batchButton = document.getElementById("npcink-my-comments-batch-delete");

  function showFeedback(message, type) {
    feedback.hidden = false;
    feedback.className = `notice inline notice-${type || "info"}`;
    feedback.textContent = message;
  }

  async function request(path, options) {
    const response = await fetch(`${config.apiBase}${path || ""}`, {
      ...options,
      headers: {
        "Content-Type": "application/json",
        "X-WP-Nonce": config.nonce,
        ...(options && options.headers ? options.headers : {}),
      },
    });
    const body = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(body.message || "请求失败，请稍后重试。");
    return body;
  }

  function escapeHtml(value) {
    const node = document.createElement("div");
    node.textContent = value == null ? "" : String(value);
    return node.innerHTML;
  }

  function renderComment(comment) {
    const disabled = comment.canDelete ? "" : "disabled";
    const editButton = comment.canEdit
      ? `<button class="button npcink-comment-edit" type="button">修改</button>`
      : "";
    return `
      <article class="npcink-comment" data-id="${comment.id}" data-hash="${comment.expectedHash}">
        <header>
          <input class="npcink-comment-select" type="checkbox" ${disabled} aria-label="选择评论 ${comment.id}">
          <strong>${escapeHtml(comment.postTitle || `文章 #${comment.postId}`)}</strong>
          <span class="npcink-comment__status">${escapeHtml(comment.status)}</span>
          <time>${escapeHtml(comment.date)}</time>
        </header>
        <div class="npcink-comment__content">${escapeHtml(comment.content)}</div>
        <textarea class="npcink-comment__editor" rows="4" hidden>${escapeHtml(comment.content)}</textarea>
        <footer>
          <a href="${escapeHtml(comment.postUrl)}" target="_blank" rel="noopener noreferrer">查看文章</a>
          ${editButton}
          <button class="button npcink-comment-delete" type="button" ${disabled}>移入回收站</button>
          ${comment.hasReplies ? "<span class=\"description\">已有回复，暂不支持修改或删除。</span>" : ""}
        </footer>
      </article>`;
  }

  async function loadComments() {
    list.innerHTML = "<p>正在加载评论……</p>";
    state.selected.clear();
    batchButton.disabled = true;
    try {
      const params = new URLSearchParams({
        page: String(state.page),
        pageSize: String(config.pageSize),
        status: state.status,
      });
      const result = await request(`?${params.toString()}`, { method: "GET" });
      state.totalPages = result.pagination.totalPages;
      list.innerHTML = result.data.length
        ? result.data.map(renderComment).join("")
        : "<p>当前没有评论。</p>";
      pageLabel.textContent = state.totalPages ? `第 ${state.page} / ${state.totalPages} 页` : "暂无分页";
      prevButton.disabled = state.page <= 1;
      nextButton.disabled = !state.totalPages || state.page >= state.totalPages;
    } catch (error) {
      list.innerHTML = "";
      showFeedback(error.message, "error");
    }
  }

  async function deleteIds(ids) {
    if (!ids.length || !window.confirm(`确定将选中的 ${ids.length} 条评论移入回收站吗？已有回复的评论不会删除。`)) return;
    const result = await request("/batch-delete", {
      method: "POST",
      body: JSON.stringify({ commentIds: ids }),
    });
    showFeedback(`已删除 ${result.summary.deleted} 条，未删除 ${result.summary.failed} 条。`, result.summary.failed ? "warning" : "success");
    await loadComments();
  }

  list.addEventListener("change", (event) => {
    const checkbox = event.target.closest(".npcink-comment-select");
    if (!checkbox) return;
    const id = Number(checkbox.closest(".npcink-comment").dataset.id);
    checkbox.checked ? state.selected.add(id) : state.selected.delete(id);
    batchButton.disabled = state.selected.size === 0;
  });

  list.addEventListener("click", async (event) => {
    const article = event.target.closest(".npcink-comment");
    if (!article) return;
    const id = Number(article.dataset.id);
    try {
      if (event.target.closest(".npcink-comment-delete")) {
        await deleteIds([id]);
      }
      if (event.target.closest(".npcink-comment-edit")) {
        const content = article.querySelector(".npcink-comment__content");
        const editor = article.querySelector(".npcink-comment__editor");
        const button = event.target.closest(".npcink-comment-edit");
        if (editor.hidden) {
          editor.hidden = false;
          content.hidden = true;
          button.textContent = "保存修改";
          editor.focus();
          return;
        }
        const updated = await request(`/${id}`, {
          method: "PATCH",
          body: JSON.stringify({ content: editor.value, expectedHash: article.dataset.hash }),
        });
        showFeedback("评论已修改并重新进入待审核状态。", "success");
        article.outerHTML = renderComment(updated);
      }
    } catch (error) {
      showFeedback(error.message, "error");
    }
  });

  document.getElementById("npcink-my-comments-compose-form").addEventListener("submit", async (event) => {
    event.preventDefault();
    const form = new FormData(event.currentTarget);
    try {
      await request("", {
        method: "POST",
        body: JSON.stringify({ postId: Number(form.get("postId")), content: form.get("content") }),
      });
      event.currentTarget.reset();
      state.page = 1;
      showFeedback("评论已提交，状态由站点评论审核规则决定。", "success");
      await loadComments();
    } catch (error) {
      showFeedback(error.message, "error");
    }
  });

  document.getElementById("npcink-my-comments-status").addEventListener("change", (event) => {
    state.status = event.target.value;
    state.page = 1;
    loadComments();
  });
  document.getElementById("npcink-my-comments-refresh").addEventListener("click", loadComments);
  batchButton.addEventListener("click", () => deleteIds(Array.from(state.selected)));
  prevButton.addEventListener("click", () => { state.page--; loadComments(); });
  nextButton.addEventListener("click", () => { state.page++; loadComments(); });

  loadComments();
})();
