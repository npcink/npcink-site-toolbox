import { notice } from "@/tool/notice";
import { __ } from "@/tool/i18n";

export const FAVORITES_STORAGE_KEY = "npcink_site_toolbox_favorites";
export const FAVORITES_CHANGED_EVENT = "npcink-site-toolbox:favorites-changed";

const normalizeFavorites = (value: unknown): string[] => {
  if (!Array.isArray(value)) return [];

  return value.filter(
    (favorite, index, favorites): favorite is string =>
      typeof favorite === "string" &&
      favorite.trim() !== "" &&
      favorites.indexOf(favorite) === index,
  );
};

const saveFavorites = (favorites: string[]): void => {
  const normalizedFavorites = normalizeFavorites(favorites);
  localStorage.setItem(FAVORITES_STORAGE_KEY, JSON.stringify(normalizedFavorites));
  window.dispatchEvent(new CustomEvent(FAVORITES_CHANGED_EVENT));
};

export function getFavorites(): string[] {
  try {
    const stored = localStorage.getItem(FAVORITES_STORAGE_KEY);
    if (stored) {
      return normalizeFavorites(JSON.parse(stored));
    }
  } catch (e) {
    console.error("读取收藏失败", e);
  }
  return [];
}

export function addFavorite(featureId: string): boolean {
  try {
    const favorites = getFavorites();
    if (!favorites.includes(featureId)) {
      favorites.push(featureId);
      saveFavorites(favorites);
      notice.success(__("已添加到常用功能"));
      return true;
    }
    return false;
  } catch (e) {
    console.error("添加收藏失败", e);
    return false;
  }
}

export function removeFavorite(featureId: string): boolean {
  try {
    const favorites = getFavorites().filter((id) => id !== featureId);
    saveFavorites(favorites);
    notice.success(__("已从常用功能移除"));
    return true;
  } catch (e) {
    console.error("移除收藏失败", e);
    return false;
  }
}

export function isFavorite(featureId: string): boolean {
  return getFavorites().includes(featureId);
}

export function toggleFavorite(featureId: string): boolean {
  if (isFavorite(featureId)) {
    removeFavorite(featureId);
    return false;
  } else {
    addFavorite(featureId);
    return true;
  }
}

export function reorderFavorites(newOrder: string[]): boolean {
  try {
    saveFavorites(newOrder);
    return true;
  } catch (e) {
    console.error("重排收藏失败", e);
    return false;
  }
}
