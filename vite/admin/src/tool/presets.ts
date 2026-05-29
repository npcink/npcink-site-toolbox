export interface Preset {
  id: string;
  name: string;
  description: string;
  wizard?: boolean;
  icon?: string;
  config: Record<string, any>;
}

export const presets: Preset[] = [
  {
    id: 'pure',
    name: '极速纯净版',
    description: '只开启站点优化和安全防护，极致性能',
    config: {
      optimize: {
        site: {
          no_escape: true,
          hide_top_toolbar: true,
          renew: true,
          remove_RSS_version: true,
        },
        medium: {
          img_add_tag: true,
          medium_add_svg: true,
        },
        admin: {
          show_id: true,
        },
      },
    },
  },
  {
    id: 'blog',
    name: '个人博客推荐',
    description: 'SEO、评论限制、阅读时间，关闭花哨特效',
    config: {
      optimize: {
        site: {
          no_escape: true,
          hide_top_toolbar: true,
          remove_RSS_version: true,
          cdn_replace: true,
        },
        medium: {
          img_add_tag: true,
          upload_auto_name: 'md5',
        },
      },
      page: {
        comment: {
          interval: true,
          interval_time: 5,
          words_number: true,
          words_number_min: 5,
          words_number_max: 500,
        },
        function: {
          add_last_update: true,
          remove_single_link: true,
        },
      },
      function: {
        seo: {
          seo_home: true,
          seo_single: true,
          seo_category: true,
        },
      },
    },
  },
  {
    id: 'company',
    name: '企业官网推荐',
    description: 'TDK、登录美化、统计，关闭评论表情包',
    config: {
      optimize: {
        site: {
          no_escape: true,
          hide_top_toolbar: true,
          remove_RSS_version: true,
        },
        medium: {
          img_add_tag: true,
        },
      },
      page: {
        function: {
          login_search: true,
        },
        jurisdiction: {
          ban_copy: true,
        },
      },
      function: {
        seo: {
          seo_home: true,
          seo_single: true,
          seo_category: true,
        },
        auxiliary: {
          baidu_tonji: '请填写百度统计ID',
        },
      },
      login: {
        beautify: {
          modify_login_link: true,
        },
        security: {
          login_code: 'math',
        },
      },
    },
  },
  {
    id: 'performance',
    name: '性能优先',
    description: '关闭所有非必要功能，仅保留核心优化',
    config: {
      optimize: {
        site: {
          no_escape: true,
          hide_top_toolbar: true,
          remove_RSS_version: true,
          renew: true,
          cdn_replace: true,
          cdn_gravatar: true,
        },
        medium: {
          no_auto_size: true,
          img_add_tag: true,
          upload_auto_name: 'md5',
        },
      },
    },
  },
  {
    id: 'security',
    name: '安全防护优先',
    description: '开启所有安全相关功能，加强站点防护',
    config: {
      optimize: {
        site: {
          remove_RSS_version: true,
          remove_sitemap_users: true,
        },
      },
      page: {
        comment: {
          interval: true,
          interval_time: 5,
          words_number: true,
          words_number_min: 5,
          english: true,
          sensitive_words: true,
          baidu_moderation: true,
        },
        function: {
          search_limit: true,
          search_limit_count: 10,
          login_search: true,
          anti_crawler: true,
          anti_crawler_max_requests: 60,
          anti_crawler_time_window: 60,
        },
        jurisdiction: {
          ban_open_weixing: true,
          ban_open_qq: true,
        },
      },
      login: {
        security: {
          login_code: 'math',
          replace_login_error: true,
        },
      },
    },
  },
  {
    id: 'monetize',
    name: '站长变现版',
    description: '开启广告、统计、SEO 等功能',
    config: {
      function: {
        seo: {
          seo_home: true,
          seo_single: true,
          seo_category: true,
        },
        auxiliary: {
          baidu_tonji: '请填写百度统计ID',
          google_tonji: '请填写Google Analytics ID',
          biying_tonji: '请填写必应统计ID',
        },
      },
    },
  },
  {
    id: 'wizard_blog',
    name: '个人博客',
    description: 'SEO 基础 + 评论防护 + 国内环境适配，适合个人写作者',
    wizard: true,
    icon: '📝',
    config: {
      optimize: {
        site: {
          no_escape: true,
          hide_top_toolbar: true,
          remove_RSS_version: true,
        },
        medium: {
          img_add_tag: true,
          upload_auto_name: 'md5',
        },
      },
      page: {
        comment: {
          interval: true,
          interval_time: 5,
          words_number: true,
          words_number_min: 5,
          words_number_max: 500,
          sensitive_words: true,
        },
        function: {
          add_last_update: true,
          search_limit: true,
          search_limit_count: 10,
        },
      },
      function: {
        seo: {
          seo_home: true,
          seo_single: true,
        },
      },
      login: {
        security: {
          login_code: 'math',
        },
      },
    },
  },
  {
    id: 'wizard_company',
    name: '企业官网',
    description: '登录安全 + 禁复制 + SEO + 统计，适合正式商业站点',
    wizard: true,
    icon: '🏢',
    config: {
      optimize: {
        site: {
          no_escape: true,
          hide_top_toolbar: true,
          remove_RSS_version: true,
          remove_sitemap_users: true,
        },
        medium: {
          img_add_tag: true,
        },
      },
      page: {
        function: {
          login_search: true,
        },
      },
      function: {
        seo: {
          seo_home: true,
          seo_single: true,
          seo_category: true,
        },
      },
      login: {
        security: {
          login_code: 'math',
        },
      },
    },
  },
  {
    id: 'wizard_content_seo',
    name: '内容 SEO 站',
    description: '全 SEO + 百度推送 + Alt 补全 + 搜索限制，适合内容营销站点',
    wizard: true,
    icon: '📊',
    config: {
      optimize: {
        site: {
          no_escape: true,
          hide_top_toolbar: true,
          remove_RSS_version: true,
          hide_email_ip: true,
        },
        medium: {
          img_add_tag: true,
          upload_auto_name: 'md5',
        },
      },
      page: {
        comment: {
          interval: true,
          interval_time: 5,
          words_number: true,
          words_number_min: 5,
          sensitive_words: true,
          baidu_moderation: true,
        },
        function: {
          add_last_update: true,
          search_limit: true,
          search_limit_count: 10,
          anti_crawler: true,
          anti_crawler_max_requests: 60,
        },
        feature: {
          reading_progress: true,
        },
      },
      function: {
        seo: {
          seo_home: true,
          seo_single: true,
          seo_category: true,
          seo_tag: true,
        },
      },
      domestic: {
        baidu_push: {
          active_push_enabled: true,
          auto_push_enabled: true,
        },
      },
    },
  },
];

const STORAGE_KEY = 'mabox_custom_presets';

export function getCustomPresets(): Preset[] {
  try {
    const stored = localStorage.getItem(STORAGE_KEY);
    if (stored) {
      return JSON.parse(stored);
    }
  } catch (e) {
    console.error('读取自定义方案失败', e);
  }
  return [];
}

export function saveCustomPreset(preset: Preset): boolean {
  try {
    const existing = getCustomPresets();
    const index = existing.findIndex((p) => p.id === preset.id);
    if (index >= 0) {
      existing[index] = preset;
    } else {
      existing.push(preset);
    }
    localStorage.setItem(STORAGE_KEY, JSON.stringify(existing));
    return true;
  } catch (e) {
    console.error('保存自定义方案失败', e);
    return false;
  }
}

export function deleteCustomPreset(presetId: string): boolean {
  try {
    const existing = getCustomPresets();
    const filtered = existing.filter((p) => p.id !== presetId);
    localStorage.setItem(STORAGE_KEY, JSON.stringify(filtered));
    return true;
  } catch (e) {
    console.error('删除自定义方案失败', e);
    return false;
  }
}

export function getAllPresets(): Preset[] {
  return [...presets, ...getCustomPresets()];
}

export function getWizardPresets(): Preset[] {
  return presets.filter((p) => p.wizard === true);
}
